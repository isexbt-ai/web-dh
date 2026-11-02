<?php

declare(strict_types=1);

namespace App\Service;

/**
 * 媒体上传服务：白名单扩展名 + finfo MIME 校验 + 大小上限。
 *
 * 存储策略（按加载时机划分）：
 * - 走 R2：仅「文章」目录。文章封面均为懒加载（loading="lazy"），
 *   不阻塞首屏，适合放对象存储。
 * - 走本地：卡片图、背景图、广告图、展示位图。这些都在首屏或需秒开，
 *   同源加载更快，且不依赖外网 R2 域名。
 *
 * R2 不可用时自动回退到本地相对路径，功能不中断。
 * 返回 path 为含 uploads/ 前缀的相对路径或 R2 完整 URL（与生产数据库存储格式一致，零迁移）。
 */
final class UploadService
{
    private const IMAGE_MIME_MAP = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
    ];

    private const VIDEO_MIME_MAP = [
        'mp4' => ['video/mp4'],
        'webm' => ['video/webm'],
        'ogv' => ['video/ogg'],
        'mov' => ['video/quicktime'],
    ];

    /** 需要上传到 R2 的子目录（其余一律只存本地，保证首屏秒开） */
    private const R2_SUBDIRS = ['articles'];

    /** @var string[] 允许的图片扩展名 */
    private array $allowedExtensions;

    /** @var string[] 允许的视频扩展名 */
    private array $allowedVideoExtensions;

    public function __construct(
        private string $uploadDir,
        private string $uploadUrlBase,
        private int $maxSize,
        array $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        array $allowedVideoExtensions = ['mp4', 'webm', 'ogv', 'mov'],
        private ?R2Service $r2 = null
    ) {
        $this->allowedExtensions = $allowedExtensions;
        $this->allowedVideoExtensions = $allowedVideoExtensions;
    }

    /**
     * 校验上传图片的扩展名与真实 MIME 是否一致。
     *
     * 单独抽出以便独立测试（image() 内部同样调用，逻辑唯一）。
     *
     * @param string $fileName 客户端文件名（取扩展名）
     * @param string $tmpPath 临时文件路径（读内容判断 MIME）
     * @return string|null 错误信息；null 表示校验通过
     */
    public function validateImage(string $fileName, string $tmpPath): ?string
    {
        $ext = strtolower((string) pathinfo($fileName, PATHINFO_EXTENSION));
        if (!in_array($ext, $this->allowedExtensions, true)) {
            return '不支持的图片格式';
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmpPath);
        if ($mime === false || (self::IMAGE_MIME_MAP[$ext] ?? null) !== $mime) {
            return '文件内容与格式不符';
        }
        return null;
    }

    /**
     * 校验上传文件大小是否超过上限。
     *
     * @param int $size 字节数
     * @param int $maxSize 上限字节数；0 表示用服务默认上限
     * @return string|null 错误信息；null 表示通过
     */
    public function validateSize(int $size, int $maxSize = 0): ?string
    {
        $limit = $maxSize > 0 ? $maxSize : $this->maxSize;
        return $size > $limit ? '文件大小超出限制' : null;
    }

    /**
     * 保存上传图片到 uploads/{$subdir}。
     *
     * @param array{name?: string, tmp_name?: string, size?: int, error?: int} $file $_FILES 单项
     * @return array{ok: bool, path: string, url: string, message: string} path 相对 uploads 根，url 可作 img src
     */
    public function image(array $file, string $subdir = 'cards'): array
    {
        $fail = static fn (string $msg): array => ['ok' => false, 'path' => '', 'url' => '', 'message' => $msg];

        if (!isset($file['error']) || (int) $file['error'] !== UPLOAD_ERR_OK) {
            return $fail('上传失败，请重试');
        }
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return $fail('上传失败，请重试');
        }

        $sizeError = $this->validateSize((int) ($file['size'] ?? 0));
        if ($sizeError !== null) {
            return $fail($sizeError);
        }

        $error = $this->validateImage((string) ($file['name'] ?? ''), (string) $file['tmp_name']);
        if ($error !== null) {
            return $fail($error);
        }

        $ext = strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        return $this->persist($file, $subdir, $ext);
    }

    /**
     * 保存上传视频到 uploads/{$subdir}。大小上限单独放大（默认 200MB）。
     *
     * @param array{name?: string, tmp_name?: string, size?: int, error?: int} $file $_FILES 单项
     * @return array{ok: bool, path: string, url: string, message: string}
     */
    public function video(array $file, string $subdir = 'showcase', int $maxSize = 0): array
    {
        $fail = static fn (string $msg): array => ['ok' => false, 'path' => '', 'url' => '', 'message' => $msg];
        $limit = $maxSize > 0 ? $maxSize : 200 * 1024 * 1024;

        if (!isset($file['error']) || (int) $file['error'] !== UPLOAD_ERR_OK) {
            $e = (int) ($file['error'] ?? 0);
            if ($e === UPLOAD_ERR_INI_SIZE || $e === UPLOAD_ERR_FORM_SIZE) {
                return $fail('视频文件过大（上限 ' . (int) ($limit / 1024 / 1024) . 'MB）');
            }
            return $fail('上传失败，请重试');
        }
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return $fail('上传失败，请重试');
        }
        if ((int) ($file['size'] ?? 0) > $limit) {
            return $fail('视频文件过大（上限 ' . (int) ($limit / 1024 / 1024) . 'MB）');
        }

        $ext = strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($ext, $this->allowedVideoExtensions, true)) {
            return $fail('不支持的视频格式（' . implode('/', $this->allowedVideoExtensions) . '）');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $accepted = self::VIDEO_MIME_MAP[$ext] ?? [];
        if ($mime === false || !in_array($mime, $accepted, true)) {
            return $fail('文件内容与格式不符');
        }

        return $this->persist($file, $subdir, $ext);
    }

    /** 落盘并返回 path/url。 */
    private function persist(array $file, string $subdir, string $ext): array
    {
        $dir = rtrim($this->uploadDir, '/') . '/' . trim($subdir, '/');
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $filename = date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $dir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return ['ok' => false, 'path' => '', 'url' => '', 'message' => '保存失败，请重试'];
        }

        $sub = trim($subdir, '/');
        $localPath = 'uploads/' . $sub . '/' . $filename;
        $localUrl = $this->uploadUrlBase . '/' . $sub . '/' . $filename;

        // 仅文章图片走 R2（懒加载，不影响首屏）；其余目录只存本地保证秒开
        if ($this->shouldPushToR2($sub)) {
            $r2Url = $this->r2?->upload($dest, $sub . '/' . $filename, $this->mimeOf($ext));
            if ($r2Url !== null && $r2Url !== '') {
                return ['ok' => true, 'path' => $r2Url, 'url' => $r2Url, 'message' => ''];
            }
        }

        return [
            'ok' => true,
            'path' => $localPath,
            'url' => $localUrl,
            'message' => '',
        ];
    }

    /** 该子目录是否需要同步到 R2（仅文章图）。 */
    private function shouldPushToR2(string $subdir): bool
    {
        if ($this->r2 === null || !$this->r2->enabled()) {
            return false;
        }
        return in_array($subdir, self::R2_SUBDIRS, true);
    }

    /** 扩展名 → MIME。 */
    private function mimeOf(string $ext): string
    {
        return self::IMAGE_MIME_MAP[$ext] ?? self::VIDEO_MIME_MAP[$ext][0] ?? 'application/octet-stream';
    }

    /** 安全删除 uploads 内文件：realpath 校验目标在 upload 根目录内。 */
    public function delete(string $relativePath): bool
    {
        $root = realpath($this->uploadDir);
        $full = realpath($this->uploadDir . '/' . ltrim($relativePath, '/'));
        if ($root === false || $full === false || !str_starts_with($full, $root . DIRECTORY_SEPARATOR)) {
            return false;
        }
        return @unlink($full);
    }

    /**
     * 删除图片：按存储位置分派。
     * - uploads/... 本地相对路径 → 删本地文件
     * - R2 公开 URL → 删 R2 对象（并尝试删同名本地副本）
     */
    public function deleteImage(string $imagePath): void
    {
        if ($imagePath === '') {
            return;
        }
        if (str_starts_with($imagePath, 'uploads/')) {
            $this->delete(substr($imagePath, strlen('uploads/')));
            return;
        }
        // R2 公开 URL → 反推 key 后删除
        if ($this->r2 !== null && $this->r2->enabled()) {
            $publicUrl = rtrim((string) config('storage.r2.public_url', ''), '/');
            if ($publicUrl !== '' && str_starts_with($imagePath, $publicUrl . '/')) {
                $key = substr($imagePath, strlen($publicUrl) + 1);
                $this->r2->delete($key);
            }
        }
    }
}
