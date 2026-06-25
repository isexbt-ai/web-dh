<?php
require_once 'includes/functions.php';

$cardId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($cardId <= 0) {
    header('Location: index.php');
    exit;
}

// 获取卡片详情
$stmt = $pdo->prepare("SELECT c.*, cat.name as category_name FROM cards c LEFT JOIN categories cat ON c.category_id = cat.id WHERE c.id = ? AND c.is_active = 1");
$stmt->execute([$cardId]);
$card = $stmt->fetch();

if (!$card) {
    header('Location: index.php');
    exit;
}

// 记录访问
recordVisit('detail');
incrementCardClick($cardId);

$config = [
    'site_title' => getConfig('site_title', '美女导航')
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($card['title']); ?> - <?php echo e($config['site_title']); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .detail-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 24px 16px;
        }

        .detail-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #f0f0f0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            overflow: hidden;
        }

        .detail-image {
            width: 100%;
            max-height: 400px;
            overflow: hidden;
        }

        .detail-image img {
            width: 100%;
            height: 400px;
            object-fit: cover;
        }

        .detail-image-placeholder {
            width: 100%;
            height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #e94560, #ff6b6b);
            font-size: 24px;
            font-weight: bold;
            color: #fff;
        }

        .detail-content {
            padding: 28px;
        }

        .detail-breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
            font-size: 13px;
            color: #999999;
        }

        .detail-breadcrumb a {
            color: #e94560;
            transition: color 0.3s ease;
        }

        .detail-breadcrumb a:hover {
            color: #c73e54;
        }

        .detail-title {
            font-size: 24px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 12px;
            line-height: 1.4;
        }

        .detail-meta {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 1px solid #f0f0f0;
        }

        .detail-meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #999999;
        }

        .detail-meta-item svg {
            width: 16px;
            height: 16px;
        }

        .detail-body {
            font-size: 15px;
            line-height: 1.8;
            color: #333333;
        }

        .detail-body p {
            margin-bottom: 16px;
        }

        .detail-body p:last-child {
            margin-bottom: 0;
        }

        .detail-body .detail-img {
            max-width: 100%;
            height: auto;
            border-radius: 12px;
            margin: 16px 0;
            display: block;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }

        .detail-actions {
            display: flex;
            gap: 12px;
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid #f0f0f0;
        }

        .detail-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 12px 28px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .detail-btn-primary {
            background: linear-gradient(135deg, #e94560, #ff6b6b);
            color: #fff;
            border: none;
            box-shadow: 0 2px 8px rgba(233, 69, 96, 0.3);
        }

        .detail-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(233, 69, 96, 0.4);
        }

        .detail-btn-secondary {
            background: #f8f9fa;
            color: #666666;
            border: 1px solid #e8e8e8;
        }

        .detail-btn-secondary:hover {
            background: #fff5f5;
            border-color: #e94560;
            color: #e94560;
        }

        .detail-empty {
            text-align: center;
            padding: 40px 0;
            color: #999999;
            font-size: 14px;
        }

        @media (max-width: 480px) {
            .detail-image img {
                height: 250px;
            }

            .detail-content {
                padding: 20px;
            }

            .detail-title {
                font-size: 20px;
            }

            .detail-actions {
                flex-direction: column;
            }

            .detail-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- 顶部栏 -->
    <header class="top-bar">
        <div class="header-left">
            <a href="index.php" style="display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px; color: #e94560;">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                <span style="font-size: 14px; font-weight: 500; color: #333333;">返回首页</span>
            </a>
        </div>
        <div class="header-right">
            <span style="font-size: 14px; color: #999999;"><?php echo e($config['site_title']); ?></span>
        </div>
    </header>

    <div class="detail-container">
        <div class="detail-card">
            <!-- 产品大图 -->
            <?php if ($card['image']): ?>
            <div class="detail-image">
                <img src="<?php echo e($card['image']); ?>" alt="<?php echo e($card['title']); ?>" loading="eager">
            </div>
            <?php else: ?>
            <div class="detail-image-placeholder">
                <span><?php echo e($card['title']); ?></span>
            </div>
            <?php endif; ?>

            <!-- 详情内容 -->
            <div class="detail-content">
                <div class="detail-breadcrumb">
                    <a href="index.php">首页</a>
                    <span>/</span>
                    <span><?php echo e($card['category_name'] ?? '未分类'); ?></span>
                    <span>/</span>
                    <span style="color: #666666;"><?php echo e($card['title']); ?></span>
                </div>

                <h1 class="detail-title"><?php echo e($card['title']); ?></h1>

                <div class="detail-meta">
                    <div class="detail-meta-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        <span><?php echo $card['click_count']; ?> 次浏览</span>
                    </div>
                    <div class="detail-meta-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        <span><?php echo date('Y-m-d', strtotime($card['created_at'])); ?></span>
                    </div>
                </div>

                <div class="detail-body">
                    <?php if (!empty($card['detail'])): ?>
                        <?php echo parseDetail($card['detail']); ?>
                    <?php else: ?>
                        <div class="detail-empty">暂无详细介绍</div>
                    <?php endif; ?>
                </div>

                <div class="detail-actions">
                    <a href="index.php" class="detail-btn detail-btn-secondary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                            <path d="M19 12H5M12 19l-7-7 7-7"/>
                        </svg>
                        返回首页
                    </a>
                    <?php if (!empty($card['link'])): ?>
                    <a href="<?php echo e($card['link']); ?>" target="_blank" class="detail-btn detail-btn-primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                            <polyline points="15 3 21 3 21 9"/>
                            <line x1="10" y1="14" x2="21" y2="3"/>
                        </svg>
                        访问网站
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 页脚 -->
    <footer class="site-footer">
        <p><?php echo e($config['site_title']); ?> - 精选优质网站导航</p>
    </footer>
</body>
</html>
