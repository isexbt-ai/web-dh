<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

// 处理清理缓存请求
$clearMessage = '';
$clearSuccess = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_cache'])) {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $cleared = clearIpLocationCache(30);
        $clearMessage = "已清理 {$cleared} 条过期缓存记录";
        $clearSuccess = true;
    } else {
        $clearMessage = '安全验证失败';
    }
}

// 获取统计数据
$todayVisits = getTodayVisits();
$todayIpCount = getTodayIpCount();
$totalVisits = getTotalVisits();
$totalPv = getTotalPv();

// 获取缓存统计
$cacheStats = getIpCacheStats();
$cacheCount = $cacheStats['total'];
$cacheSize = $cacheStats['size'] ?? 0;
$cacheSizeKB = round($cacheSize / 1024, 2);
$cacheOldest = $cacheStats['oldest'];

// 获取访问IP列表（带归属地）
// 使用LEFT JOIN批量查询IP归属地，避免N+1问题
$stmt = $pdo->query("SELECT v.ip, COUNT(*) as visit_count, MAX(v.visit_time) as last_visit, c.country as country_code, c.region, c.city, c.isp FROM visit_stats v LEFT JOIN ip_location_cache c ON v.ip = c.ip GROUP BY v.ip ORDER BY visit_count DESC LIMIT 100");
$ipList = $stmt->fetchAll();

// 为每个IP查询归属地（从缓存或API）
$domesticIps = [];
$foreignIps = [];
foreach ($ipList as &$item) {
    // 如果JOIN查询没有命中缓存，再单独查询
    if (empty($item['country_code'])) {
        $location = getIpLocation($item['ip']);
        if ($location) {
            $item['country_code'] = $location['country'];
            $item['region'] = $location['region'];
            $item['city'] = $location['city'];
            $item['isp'] = $location['isp'];
        }
    }

    // 转换国家代码为国家名
    $countryCode = $item['country_code'] ?? '-';
    $item['country'] = getCountryName($countryCode);
    $item['is_china'] = isChinaIP($countryCode);

    // 分类到国内/国外
    if ($item['is_china']) {
        $domesticIps[] = $item;
    } else {
        $foreignIps[] = $item;
    }
}
unset($item);

// 地域统计（仅国内）
$regionStats = [];
foreach ($domesticIps as $item) {
    $region = $item['region'] ?: '未知';
    if (!isset($regionStats[$region])) {
        $regionStats[$region] = 0;
    }
    $regionStats[$region]++;
}
arsort($regionStats);
$topRegions = array_slice($regionStats, 0, 5, true);

// 获取缓存中的IP数量
$cacheCount = $pdo->query("SELECT COUNT(*) FROM ip_location_cache")->fetchColumn();

// 获取缓存统计
$cacheStats = getIpCacheStats();
$cacheSizeKB = round(($cacheStats['size'] ?? 0) / 1024, 2);
$cacheOldest = $cacheStats['oldest'];

// 获取按时间排序的最近访问记录（带分页）
$timeRecordsPerPage = 50;
$timePage = isset($_GET['time_page']) ? max(1, intval($_GET['time_page'])) : 1;
$timeOffset = ($timePage - 1) * $timeRecordsPerPage;

// 获取总记录数
$timeTotalRecords = $pdo->query("SELECT COUNT(*) FROM visit_stats")->fetchColumn();
$timeTotalPages = ceil($timeTotalRecords / $timeRecordsPerPage);

// 获取按时间排序的记录（带归属地）
$stmt = $pdo->prepare("SELECT v.ip, v.page, v.user_agent, v.visit_time, c.country as country_code, c.region, c.city, c.isp FROM visit_stats v LEFT JOIN ip_location_cache c ON v.ip = c.ip ORDER BY v.visit_time DESC LIMIT ? OFFSET ?");
$stmt->execute([$timeRecordsPerPage, $timeOffset]);
$timeRecords = $stmt->fetchAll();

// 处理归属地数据
foreach ($timeRecords as &$record) {
    // 如果JOIN没有命中缓存，再单独查询
    if (empty($record['country_code'])) {
        $location = getIpLocation($record['ip']);
        if ($location) {
            $record['country_code'] = $location['country'];
            $record['region'] = $location['region'];
            $record['city'] = $location['city'];
            $record['isp'] = $location['isp'];
        }
    }

    $record['country'] = getCountryName($record['country_code'] ?? '-');
    $record['is_china'] = isChinaIP($record['country_code'] ?? '-');
}
unset($record);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo generateCsrfToken(); ?>">
    <title>IP统计 - 后台管理</title>
    <link rel="stylesheet" href="../assets/css/admin.css?v=2">
    <style>
        /* IP查询区域 */
        .ip-query-section {
            background: #ffffff;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 32px;
            border: 1px solid #f0f0f0;
            box-shadow: 0 1px 4px rgba(0,0,0,0.03);
        }

        .ip-query-box {
            display: flex;
            gap: 12px;
            max-width: 500px;
        }

        .ip-query-box input {
            flex: 1;
            padding: 12px 16px;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            background: #ffffff;
            color: #1a1a2e;
            font-size: 14px;
            outline: none;
            transition: all 0.3s ease;
        }

        .ip-query-box input:focus {
            border-color: #e94560;
            box-shadow: 0 0 0 3px rgba(233, 69, 96, 0.15);
        }

        .ip-query-box button {
            padding: 12px 24px;
            border-radius: 10px;
            border: none;
            background: linear-gradient(135deg, #e94560, #ff6b6b);
            color: #fff;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .ip-query-box button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(233, 69, 96, 0.4);
        }

        .ip-query-box button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* 查询结果 */
        .ip-result {
            margin-top: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            display: none;
        }

        .ip-result.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .ip-result-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e0e0e0;
        }

        .ip-result-header h3 {
            font-size: 20px;
            font-weight: 600;
            color: #1a1a2e;
            margin: 0;
        }

        .ip-type-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .ip-type-badge.public {
            background: rgba(78, 204, 163, 0.15);
            color: #4ecca3;
        }

        .ip-type-badge.private {
            background: rgba(233, 69, 96, 0.15);
            color: #e94560;
        }

        .ip-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
        }

        .ip-info-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .ip-info-item .label {
            font-size: 12px;
            color: #999999;
        }

        .ip-info-item .value {
            font-size: 14px;
            font-weight: 500;
            color: #1a1a2e;
        }

        /* 地域统计 */
        .region-stats {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 12px;
            margin-top: 16px;
        }

        .region-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .region-item .name {
            font-size: 14px;
            color: #333333;
        }

        .region-item .count {
            font-size: 14px;
            font-weight: 600;
            color: #e94560;
        }

        /* 加载动画 */
        .loading-spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid #f0f0f0;
            border-top-color: #e94560;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        .loading-spinner.show {
            display: inline-block;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* 错误提示 */
        .query-error {
            margin-top: 12px;
            padding: 12px 16px;
            background: rgba(244, 67, 54, 0.1);
            border: 1px solid rgba(244, 67, 54, 0.2);
            border-radius: 8px;
            color: #f44336;
            font-size: 14px;
            display: none;
        }

        .query-error.show {
            display: block;
        }

        /* 分页样式 */
        .pagination a:hover {
            background: #e94560 !important;
            color: #fff !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(233, 69, 96, 0.3);
        }

        /* 时间记录表格 */
        .data-table tbody tr:hover {
            background: rgba(233, 69, 96, 0.03);
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- 侧边栏 -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>后台管理</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7" rx="1"/>
                        <rect x="14" y="3" width="7" height="7" rx="1"/>
                        <rect x="14" y="14" width="7" height="7" rx="1"/>
                        <rect x="3" y="14" width="7" height="7" rx="1"/>
                    </svg>
                    <span>仪表盘</span>
                </a>
                <a href="config.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                    <span>站点配置</span>
                </a>
                <a href="ads.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                        <line x1="8" y1="21" x2="16" y2="21"/>
                        <line x1="12" y1="17" x2="12" y2="21"/>
                    </svg>
                    <span>广告管理</span>
                </a>
                <a href="notices.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                    <span>公告管理</span>
                </a>
                <a href="categories.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="4" y1="6" x2="20" y2="6"/>
                        <line x1="4" y1="12" x2="20" y2="12"/>
                        <line x1="4" y1="18" x2="20" y2="18"/>
                    </svg>
                    <span>分类管理</span>
                </a>
                <a href="cards.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7" rx="1"/>
                        <rect x="14" y="3" width="7" height="7" rx="1"/>
                        <rect x="14" y="14" width="7" height="7" rx="1"/>
                        <rect x="3" y="14" width="7" height="7" rx="1"/>
                    </svg>
                    <span>卡片管理</span>
                </a>
                <a href="showcase.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <span>效果展示</span>
                </a>
                <a href="links.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                    <span>链接管理</span>
                </a>
                <a href="messages.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    <span>留言管理</span>
                </a>
                <a href="ip_stats.php" class="nav-item active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="2" y1="12" x2="22" y2="12"/>
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                    </svg>
                    <span>IP统计</span>
                </a>
                <a href="password.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <span>修改密码</span>
                </a>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    <span>退出登录</span>
                </a>
            </div>
        </aside>

        <!-- 主内容区 -->
        <main class="main-content">
            <header class="page-header">
                <h1>IP统计</h1>
                <p>查看访问IP统计信息和归属地查询</p>
            </header>

            <!-- 统计卡片 -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #e94560, #ff6b6b);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $todayIpCount; ?></h3>
                        <p>今日IP数量</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4ecca3, #6bcb77);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $totalVisits; ?></h3>
                        <p>总独立IP</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #00bcd4, #4dd0e1);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                            <line x1="3" y1="9" x2="21" y2="9"/>
                            <line x1="9" y1="21" x2="9" y2="9"/>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $totalPv; ?></h3>
                        <p>总访问量</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #7c4dff, #b388ff);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="2" y1="12" x2="22" y2="12"/>
                            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $cacheCount; ?></h3>
                        <p>已缓存IP</p>
                    </div>
                </div>
            </div>

            <!-- 缓存管理 -->
            <div class="ip-query-section" style="margin-bottom: 32px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                    <div>
                        <h2 class="section-title" style="margin-bottom: 8px;">💾 IP缓存管理</h2>
                        <p style="color: #999; font-size: 13px; margin: 0;">
                            缓存大小约 <?php echo $cacheSizeKB; ?> KB
                            <?php if ($cacheOldest): ?> | 最早记录: <?php echo e($cacheOldest); ?><?php endif; ?>
                        </p>
                    </div>
                    <form method="POST" action="" style="margin: 0;">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <input type="hidden" name="clear_cache" value="1">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('确定要清理30天前的IP缓存吗？此操作不可撤销。')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px; vertical-align: middle; margin-right: 4px;">
                                <polyline points="3 6 5 6 21 6"/>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                            </svg>
                            清理过期缓存
                        </button>
                    </form>
                </div>
                <?php if ($clearMessage): ?>
                <div style="margin-top: 12px; padding: 10px 16px; border-radius: 8px; font-size: 14px; <?php echo $clearSuccess ? 'background: rgba(78, 204, 163, 0.15); color: #4ecca3;' : 'background: rgba(244, 67, 54, 0.15); color: #f44336;'; ?>">
                    <?php echo e($clearMessage); ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- IP查询工具 -->
            <div class="ip-query-section">
                <h2 class="section-title">🔍 IP归属地查询</h2>
                <div class="ip-query-box">
                    <input type="text" id="ipInput" placeholder="请输入IP地址（如：8.8.8.8）" autocomplete="off">
                    <button id="queryBtn" onclick="queryIP()">
                        <span class="btn-text">查询</span>
                        <span class="loading-spinner" id="loadingSpinner"></span>
                    </button>
                </div>
                <div class="query-error" id="queryError"></div>
                <div class="ip-result" id="ipResult">
                    <div class="ip-result-header">
                        <h3 id="resultIp"></h3>
                        <span class="ip-type-badge" id="ipTypeBadge"></span>
                    </div>
                    <div class="ip-info-grid">
                        <div class="ip-info-item">
                            <span class="label">🌏 国家/地区</span>
                            <span class="value" id="resultCountry">-</span>
                        </div>
                        <div class="ip-info-item">
                            <span class="label">📍 省份/州</span>
                            <span class="value" id="resultRegion">-</span>
                        </div>
                        <div class="ip-info-item">
                            <span class="label">🏙️ 城市</span>
                            <span class="value" id="resultCity">-</span>
                        </div>
                        <div class="ip-info-item">
                            <span class="label">🌐 运营商</span>
                            <span class="value" id="resultIsp">-</span>
                        </div>
                        <div class="ip-info-item">
                            <span class="label">🏢 组织</span>
                            <span class="value" id="resultOrg">-</span>
                        </div>
                        <div class="ip-info-item">
                            <span class="label">📡 经纬度</span>
                            <span class="value" id="resultLoc">-</span>
                        </div>
                        <div class="ip-info-item">
                            <span class="label">⏰ 时区</span>
                            <span class="value" id="resultTimezone">-</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 地域统计 -->
            <?php if (!empty($topRegions)): ?>
            <div class="table-section" style="margin-bottom: 32px;">
                <h2 class="section-title">🗺️ 地域分布TOP5</h2>
                <div class="region-stats">
                    <?php foreach ($topRegions as $region => $count): ?>
                    <div class="region-item">
                        <span class="name"><?php echo e($region); ?></span>
                        <span class="count"><?php echo $count; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- 访问记录 - 按时间排序 -->
            <div class="table-section" style="margin-bottom: 32px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                    <h2 class="section-title" style="margin-bottom: 0;">⏰ 访问记录（按时间排序）</h2>
                    <span style="color: #999999; font-size: 14px;">共 <?php echo $timeTotalRecords; ?> 条记录</span>
                </div>

                <?php if (empty($timeRecords)): ?>
                <div class="empty-state">暂无访问记录</div>
                <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">序号</th>
                            <th>IP地址</th>
                            <th>归属地</th>
                            <th>运营商</th>
                            <th>访问页面</th>
                            <th>访问时间</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($timeRecords as $index => $record): ?>
                        <tr>
                            <td><?php echo $timeOffset + $index + 1; ?></td>
                            <td>
                                <code style="background: #f0f0f0; padding: 2px 8px; border-radius: 4px; font-size: 13px;"><?php echo e($record['ip']); ?></code>
                                <?php if ($record['is_china']): ?>
                                    <span style="font-size: 12px; margin-left: 4px;">🇨🇳</span>
                                <?php else: ?>
                                    <span style="font-size: 12px; margin-left: 4px;">🌍</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $locationParts = array_filter([$record['country'], $record['region'], $record['city']], function($v) { return $v && $v !== '-'; });
                                echo e(implode(' ', $locationParts) ?: '-');
                                ?>
                            </td>
                            <td><?php echo e($record['isp'] ?: '-'); ?></td>
                            <td style="font-size: 13px; color: #666;"><?php echo e($record['page'] ?: '-'); ?></td>
                            <td style="white-space: nowrap;"><?php echo e($record['visit_time']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- 分页 -->
                <?php if ($timeTotalPages > 1): ?>
                <div style="display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 20px; flex-wrap: wrap;">
                    <?php if ($timePage > 1): ?>
                        <a href="?time_page=<?php echo $timePage - 1; ?>" style="padding: 8px 16px; background: #f0f0f0; border-radius: 8px; text-decoration: none; color: #333; font-size: 14px; transition: all 0.2s;">← 上一页</a>
                    <?php endif; ?>

                    <?php
                    // 显示分页页码
                    $startPage = max(1, $timePage - 2);
                    $endPage = min($timeTotalPages, $timePage + 2);
                    if ($startPage > 1) {
                        echo '<a href="?time_page=1" style="padding: 8px 14px; background: #f0f0f0; border-radius: 8px; text-decoration: none; color: #333; font-size: 14px;">1</a>';
                        if ($startPage > 2) {
                            echo '<span style="color: #999; padding: 8px;">...</span>';
                        }
                    }
                    for ($i = $startPage; $i <= $endPage; $i++) {
                        $isActive = $i === $timePage;
                        $bg = $isActive ? 'linear-gradient(135deg, #e94560, #ff6b6b)' : '#f0f0f0';
                        $color = $isActive ? '#fff' : '#333';
                        echo '<a href="?time_page=' . $i . '" style="padding: 8px 14px; background: ' . $bg . '; border-radius: 8px; text-decoration: none; color: ' . $color . '; font-size: 14px; font-weight: ' . ($isActive ? '600' : '400') . ';">' . $i . '</a>';
                    }
                    if ($endPage < $timeTotalPages) {
                        if ($endPage < $timeTotalPages - 1) {
                            echo '<span style="color: #999; padding: 8px;">...</span>';
                        }
                        echo '<a href="?time_page=' . $timeTotalPages . '" style="padding: 8px 14px; background: #f0f0f0; border-radius: 8px; text-decoration: none; color: #333; font-size: 14px;">' . $timeTotalPages . '</a>';
                    }
                    ?>

                    <?php if ($timePage < $timeTotalPages): ?>
                        <a href="?time_page=<?php echo $timePage + 1; ?>" style="padding: 8px 16px; background: #f0f0f0; border-radius: 8px; text-decoration: none; color: #333; font-size: 14px; transition: all 0.2s;">下一页 →</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- 访问IP列表 - 国内IP -->
            <div class="table-section" style="margin-bottom: 32px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 class="section-title" style="margin-bottom: 0;">🇨🇳 国内IP列表</h2>
                    <span style="color: #999999; font-size: 14px;">共 <?php echo count($domesticIps); ?> 条</span>
                </div>

                <?php if (empty($domesticIps)): ?>
                <div class="empty-state">暂无国内访问记录</div>
                <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>IP地址</th>
                            <th>归属地</th>
                            <th>运营商</th>
                            <th>访问次数</th>
                            <th>最后访问</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($domesticIps as $item): ?>
                        <tr>
                            <td><code style="background: #f0f0f0; padding: 2px 8px; border-radius: 4px; font-size: 13px;"><?php echo e($item['ip']); ?></code></td>
                            <td>
                                <?php
                                $locationParts = array_filter([$item['country'], $item['region'], $item['city']], function($v) { return $v && $v !== '-'; });
                                echo e(implode(' ', $locationParts) ?: '-');
                                ?>
                            </td>
                            <td><?php echo e($item['isp'] ?: '-'); ?></td>
                            <td><?php echo $item['visit_count']; ?></td>
                            <td><?php echo e($item['last_visit']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

            <!-- 访问IP列表 - 国外IP -->
            <div class="table-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 class="section-title" style="margin-bottom: 0;">🌍 国外IP列表</h2>
                    <span style="color: #999999; font-size: 14px;">共 <?php echo count($foreignIps); ?> 条</span>
                </div>

                <?php if (empty($foreignIps)): ?>
                <div class="empty-state">暂无国外访问记录</div>
                <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>IP地址</th>
                            <th>归属地</th>
                            <th>运营商</th>
                            <th>访问次数</th>
                            <th>最后访问</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($foreignIps as $item): ?>
                        <tr>
                            <td><code style="background: #f0f0f0; padding: 2px 8px; border-radius: 4px; font-size: 13px;"><?php echo e($item['ip']); ?></code></td>
                            <td>
                                <?php
                                $locationParts = array_filter([$item['country'], $item['region'], $item['city']], function($v) { return $v && $v !== '-'; });
                                echo e(implode(' ', $locationParts) ?: '-');
                                ?>
                            </td>
                            <td><?php echo e($item['isp'] ?: '-'); ?></td>
                            <td><?php echo $item['visit_count']; ?></td>
                            <td><?php echo e($item['last_visit']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="../assets/js/admin.js"></script>
    <script>
        // IP查询功能
        async function queryIP() {
            const ipInput = document.getElementById('ipInput');
            const ip = ipInput.value.trim();
            const loadingSpinner = document.getElementById('loadingSpinner');
            const queryBtn = document.getElementById('queryBtn');
            const queryError = document.getElementById('queryError');
            const ipResult = document.getElementById('ipResult');

            if (!ip) {
                showError('请输入IP地址');
                return;
            }

            // 验证IP格式
            const ipRegex = /^(\d{1,3}\.){3}\d{1,3}$/;
            if (!ipRegex.test(ip)) {
                showError('IP地址格式不正确');
                return;
            }

            // 显示加载状态
            loadingSpinner.classList.add('show');
            queryBtn.disabled = true;
            queryError.classList.remove('show');
            ipResult.classList.remove('show');

            try {
                const response = await fetch(`api/ip_query.php?ip=${encodeURIComponent(ip)}`);
                const result = await response.json();

                if (result.success && result.data) {
                    displayResult(result.data);
                } else {
                    showError(result.message || '查询失败');
                }
            } catch (error) {
                showError('网络错误，请稍后重试');
                console.error('IP查询错误:', error);
            } finally {
                loadingSpinner.classList.remove('show');
                queryBtn.disabled = false;
            }
        }

        function displayResult(data) {
            const ipResult = document.getElementById('ipResult');
            const ipTypeBadge = document.getElementById('ipTypeBadge');

            document.getElementById('resultIp').textContent = data.ip;
            document.getElementById('resultCountry').textContent = data.country_name || data.country || '-';
            document.getElementById('resultRegion').textContent = data.region || '-';
            document.getElementById('resultCity').textContent = data.city || '-';
            document.getElementById('resultIsp').textContent = data.isp || '-';
            document.getElementById('resultOrg').textContent = data.org || '-';
            document.getElementById('resultLoc').textContent = data.loc || '-';
            document.getElementById('resultTimezone').textContent = data.timezone || '-';

            // 设置IP类型标签
            const isPrivate = data.country === '内网';
            ipTypeBadge.textContent = isPrivate ? '内网IP' : '公网IP';
            ipTypeBadge.className = 'ip-type-badge ' + (isPrivate ? 'private' : 'public');

            ipResult.classList.add('show');
        }

        function showError(message) {
            const queryError = document.getElementById('queryError');
            queryError.textContent = message;
            queryError.classList.add('show');
        }

        // 回车键查询
        document.getElementById('ipInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                queryIP();
            }
        });
    </script>
</body>
</html>
