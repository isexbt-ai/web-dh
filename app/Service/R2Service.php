<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Cloudflare R2 对象存储服务（S3 兼容，cURL + SigV4 签名，无 aws-sdk 依赖）。
 * 密钥从 config('storage.r2')（读自 .env）注入，禁止硬编码。
 */
final class R2Service
{
    /** @var array{enabled: bool, account_id: string, access_key_id: string, secret_access_key: string, bucket: string, public_url: string} */
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function enabled(): bool
    {
        return (bool) ($this->config['enabled'] ?? false);
    }

    /** R2 公开访问 URL（拼接 public_url + key）。 */
    public function url(string $key): string
    {
        return rtrim((string) ($this->config['public_url'] ?? ''), '/') . '/' . ltrim($key, '/');
    }

    /** 上传本地文件到 R2，成功返回公开 URL，失败返回 null。 */
    public function upload(string $localPath, string $key, string $contentType = 'application/octet-stream'): ?string
    {
        if (!$this->enabled() || !is_file($localPath)) {
            return null;
        }
        $body = (string) file_get_contents($localPath);
        $payloadHash = hash('sha256', $body);

        $headers = $this->signedHeaders('PUT', $key, $payloadHash, $contentType);
        $headers['Content-Type'] = $contentType;

        $ch = curl_init($this->endpoint() . '/' . ltrim($key, '/'));
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 60,
        ]);
        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($code < 200 || $code >= 300) {
            error_log('[R2] upload failed key=' . $key . ' code=' . $code . ' resp=' . substr((string) $resp, 0, 200));
            return null;
        }
        return $this->url($key);
    }

    public function delete(string $key): bool
    {
        if (!$this->enabled()) {
            return false;
        }
        $headers = $this->signedHeaders('DELETE', $key, '', '');
        $ch = curl_init($this->endpoint() . '/' . ltrim($key, '/'));
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
        ]);
        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        return $code >= 200 && $code < 300;
    }

    private function endpoint(): string
    {
        return 'https://' . $this->config['account_id'] . '.r2.cloudflarestorage.com/' . $this->config['bucket'];
    }

    /** 生成 AWS SigV4 签名请求头。 */
    private function signedHeaders(string $method, string $key, string $payloadHash, string $contentType): array
    {
        $accessKey = (string) $this->config['access_key_id'];
        $secret = (string) $this->config['secret_access_key'];
        $region = 'auto';
        $service = 's3';
        $host = parse_url($this->endpoint(), PHP_URL_HOST) ?? '';
        $now = gmdate('Ymd\THis\Z');
        $dateStamp = gmdate('Ymd');

        $canonicalUri = '/' . implode('/', array_map('rawurlencode', explode('/', ltrim($key, '/'))));
        $canonicalHeaders = "host:$host\nx-amz-content-sha256:$payloadHash\nx-amz-date:$now\n";
        $signedHeadersList = 'host;x-amz-content-sha256;x-amz-date';
        $canonicalRequest = $method . "\n" . $canonicalUri . "\n\n" . $canonicalHeaders . "\n" . $signedHeadersList . "\n" . $payloadHash;

        $scope = $dateStamp . '/' . $region . '/' . $service . '/aws4_request';
        $stringToSign = "AWS4-HMAC-SHA256\n" . $now . "\n" . $scope . "\n" . hash('sha256', $canonicalRequest);

        $kDate = hash_hmac('sha256', $dateStamp, 'AWS4' . $secret, true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        $authorization = "AWS4-HMAC-SHA256 Credential={$accessKey}/{$scope}, SignedHeaders={$signedHeadersList}, Signature={$signature}";
        return [
            'Authorization: ' . $authorization,
            'x-amz-date: ' . $now,
            'x-amz-content-sha256: ' . $payloadHash,
        ];
    }
}
