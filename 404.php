<?php
/**
 * 404 自定义错误页面
 */
require_once 'includes/functions.php';

http_response_code(404);
$siteTitle = getConfig('site_title', '美女导航');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>页面未找到 - <?php echo e($siteTitle); ?></title>
    <meta name="robots" content="noindex,nofollow">
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime('assets/css/style.css'); ?>">
    <style>
        .error-page {
            min-height: 80vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 40px 20px;
        }
        .error-code {
            font-size: 120px;
            font-weight: 800;
            background: linear-gradient(135deg, #e94560, #ff6b6b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 16px;
        }
        .error-title {
            font-size: 24px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 8px;
        }
        .error-desc {
            font-size: 15px;
            color: #999;
            margin-bottom: 32px;
        }
        .error-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 32px;
            border-radius: 25px;
            background: linear-gradient(135deg, #e94560, #ff6b6b);
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 4px 16px rgba(233, 69, 96, 0.3);
            transition: all 0.3s ease;
        }
        .error-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(233, 69, 96, 0.4);
        }
    </style>
</head>
<body>
    <div class="error-page">
        <div class="error-code">404</div>
        <div class="error-title">页面未找到</div>
        <div class="error-desc">你访问的页面不存在或已被删除</div>
        <a href="/" class="error-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            返回首页
        </a>
    </div>
</body>
</html>
