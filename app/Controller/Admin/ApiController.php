<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Ad;
use App\Model\Article;
use App\Model\Card;
use App\Model\Category;
use App\Model\Gallery;
use App\Model\Link;
use App\Model\Message;
use App\Model\Notice;
use App\Model\Showcase;
use App\Service\CacheService;
use App\Service\EntityRegistry;
use App\Service\PingService;
use App\Service\SettingService;
use App\Service\UploadService;
use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * 后台统一数据接口：save/delete/upload/ip_query。
 * 全部要求已登录 + CSRF；写成功后清整页缓存。
 */
final class ApiController
{
    /** 后台可保存的配置键白名单（与 SettingController 一致） */
    private const CONFIG_KEYS = [
        'site_title', 'site_subtitle', 'site_description', 'site_keywords',
        'site_theme',
        'site_bg_image', 'site_bg_overlay',
        'card_sort_method', 'guestbook_enabled',
        'guestbook_title', 'guestbook_subtitle', 'guestbook_image', 'guestbook_notice',
        'umami_enabled', 'umami_script_url', 'umami_website_id',
        'cards_per_row_desktop', 'cards_per_row_tablet', 'cards_per_row_mobile',
        'visitor_base_offset', 'visitor_daily_increment',
        'baidu_push_token', 'indexnow_key',
    ];

    /** 允许的上传子目录白名单（防止 type 参数穿越到 uploads 之外） */
    private const UPLOAD_SUBDIRS = ['cards', 'articles', 'showcase', 'ads', 'background'];

    public function __construct(
        private Ad $ad,
        private Notice $notice,
        private Article $article,
        private Category $category,
        private Card $card,
        private Link $link,
        private Showcase $showcase,
        private Gallery $gallery,
        private Message $message,
        private SettingService $settings,
        private UploadService $upload,
        private CacheService $cache,
        private PingService $ping,
        private PDO $pdo
    ) {
    }

    public function save(Request $request, Response $response): Response
    {
        $action = (string) ($request->getQueryParams()['action'] ?? '');
        $body = (array) ($request->getParsedBody() ?: []);
        $id = (int) ($body['id'] ?? 0);

        try {
            // 注册表驱动的通用实体保存
            if (EntityRegistry::has($action)) {
                $result = $this->saveEntity($action, $body, $id);
                if ($result !== null) {
                    return $this->json($response, false, $result);
                }
            } else {
                switch ($action) {
                    case 'message':
                        // 审核/回复留言
                        $act = (string) ($body['op'] ?? '');
                        if ($act === 'reply') {
                            $this->message->reply($id, trim((string) ($body['reply'] ?? '')));
                        } elseif ($act === 'toggle') {
                            $this->message->setActive($id, (int) ($body['is_active'] ?? 0));
                        }
                        break;

                    case 'config':
                        if (!$this->isSuperAdmin()) {
                            return $this->json($response, false, '仅超级管理员可修改站点配置');
                        }
                        $configs = [];
                        foreach (self::CONFIG_KEYS as $key) {
                            if (array_key_exists($key, $body)) {
                                $configs[$key] = (string) $body[$key];
                            }
                        }
                        $this->settings->setMany($configs);
                        break;

                    default:
                        return $this->json($response, false, '未知操作');
                }
            }
        } catch (\Throwable $e) {
            error_log('[admin/api/save] ' . $action . ' failed, user=' . ($_SESSION['admin_username'] ?? '?') . ' msg=' . $e->getMessage());
            return $this->json($response, false, '保存失败，请重试');
        }

        // 推送 SEO 索引（仅影响前台内容时；失败不阻断主流程）
        $this->pingSeoUrls($action, $id);

        $this->cache->clear();
        return $this->json($response, true, '保存成功');
    }

    /**
     * 通用实体保存流程（注册表驱动）。
     *
     * @param array<string, mixed> $body 请求体
     * @param int $id 0 表示新增
     * @return string|null 错误信息；null 表示保存成功
     */
    private function saveEntity(string $entity, array $body, int $id): ?string
    {
        $def = EntityRegistry::definition($entity);
        $data = $this->clean($body, $def['fields']);

        // URL 字段伪协议过滤
        foreach ($def['url'] ?? [] as $field) {
            $data[$field] = $this->safeUrl((string) ($data[$field] ?? ''));
        }

        // 必填校验（trim 后判空）
        foreach ($def['require'] ?? [] as $field) {
            if (trim((string) ($data[$field] ?? '')) === '') {
                return self::REQUIRE_MESSAGE[$entity] ?? '必填项不能为空';
            }
        }

        // 字符串长度截断
        foreach ($def['max'] ?? [] as $field => $max) {
            $data[$field] = mb_substr(trim((string) ($data[$field] ?? '')), 0, $max);
        }

        // 实体特有处理
        $error = $this->prepareEntityData($entity, $data);
        if ($error !== null) {
            return $error;
        }

        if ($id > 0) {
            $this->entityModel($entity)->update($id, $data);
        } else {
            $this->createEntity($entity, $data);
        }
        return null;
    }

    /** 各实体的必填项错误提示。 */
    private const REQUIRE_MESSAGE = [
        'category' => '分类名称不能为空',
        'card' => '卡片标题不能为空',
        'notice' => '公告标题不能为空',
        'article' => '文章标题不能为空',
        'showcase' => '展示标题不能为空',
        'gallery' => '相册名称不能为空',
        'link' => '链接标题和地址不能为空',
    ];

    /**
     * 实体特有数据处理（slug 生成、媒体类型校正等）。
     *
     * @param array<string, mixed> $data
     * @return string|null 错误信息；null 表示通过
     */
    private function prepareEntityData(string $entity, array &$data): ?string
    {
        switch ($entity) {
            case 'article':
                if (trim((string) ($data['slug'] ?? '')) === '') {
                    $data['slug'] = str_slug((string) ($data['title'] ?? ''));
                }
                $data['slug'] = mb_substr($this->safeSlug((string) $data['slug']), 0, 50);
                $data['content'] = self::sanitizeArticleBody((string) ($data['content'] ?? ''));
                break;

            case 'showcase':
                $data['media_type'] = in_array($data['media_type'] ?? '', ['image', 'video'], true)
                    ? (string) $data['media_type']
                    : 'image';
                if ($data['media_type'] === 'video' && trim((string) ($data['video_url'] ?? '')) === '') {
                    return '视频类型必须上传视频文件';
                }
                break;
        }
        return null;
    }

    /** 实体对应的 Model 实例。 */
    private function entityModel(string $entity): object
    {
        return match ($entity) {
            'category' => $this->category,
            'card' => $this->card,
            'ad' => $this->ad,
            'notice' => $this->notice,
            'article' => $this->article,
            'showcase' => $this->showcase,
            'gallery' => $this->gallery,
            'link' => $this->link,
            default => throw new \InvalidArgumentException('未知实体: ' . $entity),
        };
    }

    /**
     * 实体新增（Category 的 create 签名与其余不同，单独适配）。
     *
     * @param array<string, mixed> $data
     */
    private function createEntity(string $entity, array $data): void
    {
        if ($entity === 'category') {
            $this->category->create(
                (string) $data['name'],
                (int) $data['sort_order'],
                (int) $data['is_active']
            );
            return;
        }
        $this->entityModel($entity)->create($data);
    }

    /**
     * 把受影响的页面 URL 投到 Baidu / IndexNow。
     * 仅 card / article / category / showcase / link 触发；config / message 不推。
     */
    private function pingSeoUrls(string $action, int $id): void
    {
        $urls = [];
        try {
            switch ($action) {
                case 'card':
                    $urls[] = '/';
                    if ($id > 0) {
                        $urls[] = '/card/' . $id . '.html';
                        $card = $this->card->find($id, true);
                        if ($card !== null) {
                            $urls[] = '/category/' . (int) $card['category_id'] . '.html';
                        }
                    }
                    break;
                case 'article':
                    $urls[] = '/articles';
                    if ($id > 0) {
                        $article = $this->article->find($id, true);
                        if ($article !== null) {
                            $urls[] = '/article/' . $id . '-' . (string) $article['slug'] . '.html';
                        }
                    }
                    break;
                case 'category':
                    $urls[] = '/';
                    if ($id > 0) {
                        $urls[] = '/category/' . $id . '.html';
                    }
                    break;
                case 'showcase':
                    $urls[] = '/showcase';
                    break;
                default:
                    return;
            }
            $this->ping->pingUrls($urls);
        } catch (\Throwable $e) {
            error_log('[admin/api/ping] ' . $action . ' failed, msg=' . $e->getMessage());
        }
    }

    public function delete(Request $request, Response $response): Response
    {
        $body = (array) ($request->getParsedBody() ?: []);
        $action = (string) ($body['action'] ?? '');
        $id = (int) ($body['id'] ?? 0);

        try {
            switch ($action) {
                case 'message':
                    $this->message->delete($id);
                    break;

                default:
                    if (!EntityRegistry::has($action)) {
                        return $this->json($response, false, '未知操作');
                    }
                    $this->deleteEntity($action, $id);
            }
        } catch (\Throwable $e) {
            error_log('[admin/api/delete] ' . $action . ' failed, user=' . ($_SESSION['admin_username'] ?? '?') . ' msg=' . $e->getMessage());
            return $this->json($response, false, '删除失败，请重试');
        }

        $this->cache->clear();
        return $this->json($response, true, '删除成功');
    }

    /**
     * 通用实体删除流程：删除记录并清理其关联的本地图片。
     */
    private function deleteEntity(string $entity, int $id): void
    {
        // 删除前取出图片路径（部分实体需同时清理多个图片字段）
        $images = match ($entity) {
            'card' => [$this->card->imageOf($id)['image'] ?? null],
            'ad' => [$this->ad->imageOf($id)['image'] ?? null],
            'article' => [$this->article->coverImageOf($id)['cover_image'] ?? null],
            'showcase' => (static function (array $row): array {
                return [$row['image'] ?? null, $row['video_url'] ?? null, $row['poster_url'] ?? null];
            })($this->showcase->imageOf($id) ?? []),
            default => [],
        };

        $this->entityModel($entity)->delete($id);

        foreach ($images as $image) {
            $this->deleteImage($image);
        }
    }

    public function upload(Request $request, Response $response): Response
    {
        $type = trim((string) ($request->getQueryParams()['type'] ?? 'cards'));
        $kind = (string) ($request->getQueryParams()['kind'] ?? 'image');
        if (!in_array($type, self::UPLOAD_SUBDIRS, true)) {
            return $this->json($response, false, '不支持的上传目录');
        }
        $files = $request->getUploadedFiles();
        $file = $files['image'] ?? $files['video'] ?? null;

        if ($file === null) {
            return $this->json($response, false, $kind === 'video' ? '请选择视频文件' : '请选择图片文件');
        }

        $payload = [
            'name' => $file->getClientFilename(),
            'tmp_name' => $file->getFilePath(),
            'size' => $file->getSize(),
            'error' => $file->getError(),
        ];

        if ($kind === 'video') {
            // 视频仅用于效果展示，统一落到 showcase 目录
            $result = $this->upload->video($payload, 'showcase');
        } else {
            if ($file->getError() !== UPLOAD_ERR_OK) {
                return $this->json($response, false, '请选择图片文件');
            }
            $result = $this->upload->image($payload, $type);
        }

        if (!$result['ok']) {
            return $this->json($response, false, $result['message']);
        }
        return $this->json($response, true, '上传成功', ['path' => $result['path'], 'url' => $result['url']]);
    }

    public function ipQuery(Request $request, Response $response): Response
    {
        $body = (array) ($request->getParsedBody() ?: []);
        $ip = trim((string) ($body['ip'] ?? ''));
        $result = ['ip' => $ip, 'country' => '', 'region' => '', 'city' => '', 'isp' => ''];
        if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
            $stmt = $this->pdo->prepare('SELECT country, region, city, isp FROM ip_location_cache WHERE ip = ?');
            $stmt->execute([$ip]);
            $row = $stmt->fetch();
            if ($row !== false) {
                $result = [
                    'ip' => $ip,
                    'country' => (string) ($row['country'] ?? ''),
                    'region' => (string) ($row['region'] ?? ''),
                    'city' => (string) ($row['city'] ?? ''),
                    'isp' => (string) ($row['isp'] ?? ''),
                ];
            }
        }
        return $this->json($response, true, '', $result);
    }

    private function isSuperAdmin(): bool
    {
        return ($_SESSION['admin_role'] ?? '') === 'superadmin';
    }

    /** 按类型清洗字段：int 强制转 int，bool 转 0/1，其余按字符串长度限制。 */
    private function clean(array $body, array $types): array
    {
        $out = [];
        foreach ($types as $key => $type) {
            $value = $body[$key] ?? null;
            $out[$key] = match ($type) {
                'int' => $value === null ? 0 : (int) $value,
                'bool' => $value === null ? 1 : ((int) $value === 1 ? 1 : 0),
                default => is_string($value) ? $value : (string) ($value ?? ''),
            };
        }
        return $out;
    }

    /** URL 安全过滤：禁止 javascript:/data:/vbscript: 伪协议，其余原样。 */
    private function safeUrl(string $url): string
    {
        $url = trim($url);
        return preg_match('/^\s*(javascript|data|vbscript|about|chrome):/i', $url) ? '' : $url;
    }

    /** slug 清洗：仅保留字母数字与短横线。 */
    private function safeSlug(string $slug): string
    {
        $slug = preg_replace('/[^a-zA-Z0-9-]/', '', $slug) ?? '';
        return trim($slug, '-');
    }

    /**
     * 文章正文净化：剥离脚本与伪协议，保留排版用 HTML 与正常交互。
     *
     * 正文在详情页按原始 HTML 输出（支持 h2/p/strong/img 等排版），
     * 因此需在写入前阻断可执行内容：<script>、iframe 等嵌入标签、
     * javascript:/data: 等伪协议。
     *
     * 注意：不剥离 onclick 等内联事件属性 —— 现网正文存在网盘付费下载
     * 链接依赖 onclick 改写 href，剥离会破坏下载功能。
     */
    public static function sanitizeArticleBody(string $html): string
    {
        if ($html === '') {
            return '';
        }
        // 移除 script 标签及其内容
        $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html) ?? $html;
        // 移除可嵌入文档的标签
        $html = preg_replace('#</?(iframe|object|embed|base)\b[^>]*>#i', '', $html) ?? $html;
        // 移除伪协议 URL（javascript:/data:/vbscript:）
        $html = preg_replace('#(src|href)\s*=\s*(["\'])\s*(?:javascript|data|vbscript):[^"\']*\2#i', '$1=$2$2', $html) ?? $html;
        return $html;
    }

    /** 安全删除图片（本地 uploads 路径或 R2 公开 URL）。 */
    private function deleteImage(?string $imagePath): void
    {
        if ($imagePath === null || $imagePath === '') {
            return;
        }
        $this->upload->deleteImage($imagePath);
    }

    private function json(Response $response, bool $success, string $message, array $data = []): Response
    {
        $response->getBody()->write((string) json_encode(
            array_merge(['success' => $success, 'message' => $message], $data),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
