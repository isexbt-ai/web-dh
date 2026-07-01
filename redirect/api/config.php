<?php
/**
 * redirect/api/config.php - 跳转页配置API（动态子域名版）
 * 生成随机子域名，实现每次访问都是新域名
 */

require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

/**
 * 生成随机子域名
 * @param int $length 子域名长度
 * @return string 随机子域名
 */
function generateRandomSubdomain($length = 6) {
    $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    $subdomain = '';
    for ($i = 0; $i < $length; $i++) {
        $subdomain .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $subdomain;
}

// 获取配置
$countdown = intval(getConfig('redirect_countdown', '3'));
$checkTimeout = intval(getConfig('redirect_check_timeout', '3000'));
$fallbackFirst = getConfig('redirect_fallback_first', '1') === '1';
$subdomainLength = intval(getConfig('redirect_subdomain_length', '6'));
$mainDomain = getConfig('redirect_main_domain', '');

// 如果没有配置主域名，使用当前请求的域名
if (empty($mainDomain)) {
    $mainDomain = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // 移除可能的端口号
    $mainDomain = explode(':', $mainDomain)[0];
}

// 生成随机子域名
$randomSubdomain = generateRandomSubdomain(max(4, min(12, $subdomainLength)));
$targetUrl = 'https://' . $randomSubdomain . '.' . $mainDomain . '/';

// 构建配置响应
$config = [
    'version' => '1.0.0',
    'main' => [
        'url' => $targetUrl,
        'name' => '导航站',
        'priority' => 1
    ],
    'backups' => [],
    'settings' => [
        'checkTimeout' => $checkTimeout,
        'countdownSeconds' => $countdown,
        'fallbackToFirst' => $fallbackFirst
    ]
];

echo json_encode($config);
