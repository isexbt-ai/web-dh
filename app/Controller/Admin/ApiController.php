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
        'card_sort_method', 'guestbook_enabled',
        'guestbook_title', 'guestbook_subtitle', 'guestbook_image', 'guestbook_notice',
        'umami_enabled', 'umami_script_url', 'umami_website_id',
        'cards_per_row_desktop', 'cards_per_row_tablet', 'cards_per_row_mobile',
        'visitor_base_offset', 'visitor_daily_increment',
        'baidu_push_token', 'indexnow_key',
    ];

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
            switch ($action) {
                case 'category':
                    $data = $this->clean($body, ['name' => 'string', 'sort_order' => 'int', 'is_active' => 'bool']);
                    $data['name'] = mb_substr(trim((string) $data['name']), 0, 30);
                    if ($data['name'] === '') {
                        return $this->json($response, false, '分类名称不能为空');
                    }
                    $id > 0 ? $this->category->update($id, $data) : $this->category->create($data['name'], (int) $data['sort_order'], (int) $data['is_active']);
                    break;

                case 'card':
                    $data = $this->clean($body, ['category_id' => 'int', 'title' => 'string', 'image' => 'string', 'link' => 'string', 'detail' => 'string', 'card_type' => 'string', 'image_width' => 'int', 'image_height' => 'int', 'badge_text' => 'string', 'sort_order' => 'int', 'is_active' => 'bool']);
                    $data['link'] = $this->safeUrl((string) $data['link']);
                    if (trim((string) $data['title']) === '') {
                        return $this->json($response, false, '卡片标题不能为空');
                    }
                    $id > 0 ? $this->card->update($id, $data) : $this->card->create($data);
                    break;

                case 'ad':
                    $data = $this->clean($body, ['title' => 'string', 'image' => 'string', 'link' => 'string', 'sort_order' => 'int', 'is_active' => 'bool']);
                    $data['link'] = $this->safeUrl((string) $data['link']);
                    $id > 0 ? $this->ad->update($id, $data) : $this->ad->create($data);
                    break;

                case 'notice':
                    $data = $this->clean($body, ['title' => 'string', 'content' => 'string', 'sort_order' => 'int', 'is_active' => 'bool']);
                    if (trim((string) $data['title']) === '') {
                        return $this->json($response, false, '公告标题不能为空');
                    }
                    $id > 0 ? $this->notice->update($id, $data) : $this->notice->create($data);
                    break;

                case 'article':
                    $data = $this->clean($body, ['title' => 'string', 'slug' => 'string', 'summary' => 'string', 'content' => 'string', 'cover_image' => 'string', 'keywords' => 'string', 'is_active' => 'bool']);
                    if (trim((string) $data['title']) === '') {
                        return $this->json($response, false, '文章标题不能为空');
                    }
                    if (trim((string) $data['slug']) === '') {
                        $data['slug'] = str_slug((string) $data['title']);
                    }
                    $data['slug'] = mb_substr($this->safeSlug((string) $data['slug']), 0, 50);
                    if ($id > 0) {
                        $this->article->update($id, $data);
                    } else {
                        $this->article->create($data);
                    }
                    break;

                case 'showcase':
                    $data = $this->clean($body, ['title' => 'string', 'image' => 'string', 'media_type' => 'string', 'gallery_id' => 'int', 'sort_order' => 'int', 'is_active' => 'bool']);
                    if (trim((string) $data['title']) === '') {
                        return $this->json($response, false, '展示标题不能为空');
                    }
                    $id > 0 ? $this->showcase->update($id, $data) : $this->showcase->create($data);
                    break;

                case 'gallery':
                    $data = $this->clean($body, ['title' => 'string', 'description' => 'string', 'cover_image' => 'string', 'sort_order' => 'int', 'is_active' => 'bool']);
                    if (trim((string) $data['title']) === '') {
                        return $this->json($response, false, '相册名称不能为空');
                    }
                    $id > 0 ? $this->gallery->update($id, $data) : $this->gallery->create($data);
                    break;

                case 'link':
                    $data = $this->clean($body, ['title' => 'string', 'url' => 'string', 'icon' => 'string', 'sort_order' => 'int', 'is_active' => 'bool']);
                    $data['url'] = $this->safeUrl((string) $data['url']);
                    if (trim((string) $data['title']) === '' || $data['url'] === '') {
                        return $this->json($response, false, '链接标题和地址不能为空');
                    }
                    $id > 0 ? $this->link->update($id, $data) : $this->link->create($data);
                    break;

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
                case 'category':
                    $this->category->delete($id);
                    break;
                case 'card':
                    $row = $this->card->imageOf($id);
                    $this->card->delete($id);
                    $this->deleteImage($row['image'] ?? null);
                    break;
                case 'ad':
                    $row = $this->ad->imageOf($id);
                    $this->ad->delete($id);
                    $this->deleteImage($row['image'] ?? null);
                    break;
                case 'notice':
                    $this->notice->delete($id);
                    break;
                case 'article':
                    $row = $this->article->coverImageOf($id);
                    $this->article->delete($id);
                    $this->deleteImage($row['cover_image'] ?? null);
                    break;
                case 'showcase':
                    $row = $this->showcase->imageOf($id);
                    $this->showcase->delete($id);
                    $this->deleteImage($row['image'] ?? null);
                    break;
                case 'gallery':
                    $this->gallery->delete($id);
                    break;
                case 'link':
                    $this->link->delete($id);
                    break;
                case 'message':
                    $this->message->delete($id);
                    break;
                default:
                    return $this->json($response, false, '未知操作');
            }
        } catch (\Throwable $e) {
            error_log('[admin/api/delete] ' . $action . ' failed, user=' . ($_SESSION['admin_username'] ?? '?') . ' msg=' . $e->getMessage());
            return $this->json($response, false, '删除失败，请重试');
        }

        $this->cache->clear();
        return $this->json($response, true, '删除成功');
    }

    public function upload(Request $request, Response $response): Response
    {
        $type = trim((string) ($request->getQueryParams()['type'] ?? 'cards'));
        $files = $request->getUploadedFiles();
        $file = $files['image'] ?? null;

        if ($file === null || $file->getError() !== UPLOAD_ERR_OK) {
            return $this->json($response, false, '请选择图片文件');
        }

        $result = $this->upload->image([
            'name' => $file->getClientFilename(),
            'tmp_name' => $file->getFilePath(),
            'size' => $file->getSize(),
            'error' => $file->getError(),
        ], $type);

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

    /** 安全删除图片（仅限 uploads 内相对路径）。 */
    private function deleteImage(?string $imagePath): void
    {
        if ($imagePath === null || $imagePath === '') {
            return;
        }
        // 仅删除本地上传路径（uploads/...），R2 外部 URL 不处理
        if (str_starts_with($imagePath, 'uploads/')) {
            $this->upload->delete(substr($imagePath, strlen('uploads/')));
        }
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
