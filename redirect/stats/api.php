<?php
/**
 * stats/api.php - 统计API接口
 * 接收前端上报，提供统计数据查询
 */

require_once 'db.php';

header('Content-Type: application/json; charset=utf-8');

// 安全：简单密码保护（生产环境建议改为更强的认证）
$adminPassword = getenv('STATS_PASSWORD') ?: 'admin123';

// 国家代码转中文名（常用国家）
$countryMap = [
    'CN' => '中国', 'US' => '美国', 'JP' => '日本', 'KR' => '韩国',
    'SG' => '新加坡', 'HK' => '中国香港', 'TW' => '中国台湾',
    'GB' => '英国', 'DE' => '德国', 'FR' => '法国', 'RU' => '俄罗斯',
    'IN' => '印度', 'BR' => '巴西', 'CA' => '加拿大', 'AU' => '澳大利亚',
    'TH' => '泰国', 'VN' => '越南', 'MY' => '马来西亚', 'PH' => '菲律宾',
    'ID' => '印度尼西亚', 'MX' => '墨西哥', 'IT' => '意大利', 'ES' => '西班牙',
    'NL' => '荷兰', 'SE' => '瑞典', 'CH' => '瑞士', 'TR' => '土耳其',
    'PL' => '波兰', 'BE' => '比利时', 'AT' => '奥地利', 'NO' => '挪威',
    'DK' => '丹麦', 'FI' => '芬兰', 'IE' => '爱尔兰', 'PT' => '葡萄牙',
    'GR' => '希腊', 'CZ' => '捷克', 'HU' => '匈牙利', 'RO' => '罗马尼亚',
    'UA' => '乌克兰', 'IL' => '以色列', 'AE' => '阿联酋', 'SA' => '沙特阿拉伯',
    'ZA' => '南非', 'EG' => '埃及', 'NG' => '尼日利亚', 'KE' => '肯尼亚',
    'CL' => '智利', 'CO' => '哥伦比亚', 'PE' => '秘鲁', 'AR' => '阿根廷',
    'NZ' => '新西兰', 'PK' => '巴基斯坦', 'BD' => '孟加拉国', 'LK' => '斯里兰卡',
    'MM' => '缅甸', 'KH' => '柬埔寨', 'LA' => '老挝', 'MN' => '蒙古',
    'NP' => '尼泊尔', 'BT' => '不丹', 'MV' => '马尔代夫', 'AF' => '阿富汗',
    'IR' => '伊朗', 'IQ' => '伊拉克', 'SY' => '叙利亚', 'JO' => '约旦',
    'LB' => '黎巴嫩', 'KW' => '科威特', 'QA' => '卡塔尔', 'BH' => '巴林',
    'OM' => '阿曼', 'YE' => '也门', 'KZ' => '哈萨克斯坦', 'UZ' => '乌兹别克斯坦',
    'KG' => '吉尔吉斯斯坦', 'TJ' => '塔吉克斯坦', 'TM' => '土库曼斯坦',
    'GE' => '格鲁吉亚', 'AZ' => '阿塞拜疆', 'AM' => '亚美尼亚',
    'BY' => '白俄罗斯', 'MD' => '摩尔多瓦', 'LT' => '立陶宛', 'LV' => '拉脱维亚',
    'EE' => '爱沙尼亚', 'SK' => '斯洛伐克', 'SI' => '斯洛文尼亚', 'HR' => '克罗地亚',
    'BA' => '波黑', 'RS' => '塞尔维亚', 'ME' => '黑山', 'MK' => '北马其顿',
    'AL' => '阿尔巴尼亚', 'BG' => '保加利亚', 'CY' => '塞浦路斯', 'MT' => '马耳他',
    'IS' => '冰岛', 'LU' => '卢森堡', 'LI' => '列支敦士登', 'MC' => '摩纳哥',
    'SM' => '圣马力诺', 'AD' => '安道尔', 'VA' => '梵蒂冈',
];

function getCountryName($code) {
    global $countryMap;
    return $countryMap[$code] ?? '未知(' . $code . ')';
}

// 解析User-Agent
function parseUserAgent($ua) {
    $device = 'desktop';
    $browser = 'unknown';
    $os = 'unknown';

    if (empty($ua)) return compact('device', 'browser', 'os');

    $ua = strtolower($ua);

    // 设备类型
    if (strpos($ua, 'mobile') !== false || strpos($ua, 'android') !== false || strpos($ua, 'iphone') !== false) {
        $device = 'mobile';
    } elseif (strpos($ua, 'tablet') !== false || strpos($ua, 'ipad') !== false) {
        $device = 'tablet';
    }

    // 浏览器
    if (strpos($ua, 'chrome') !== false && strpos($ua, 'edg') === false) {
        $browser = 'Chrome';
    } elseif (strpos($ua, 'firefox') !== false) {
        $browser = 'Firefox';
    } elseif (strpos($ua, 'safari') !== false && strpos($ua, 'chrome') === false) {
        $browser = 'Safari';
    } elseif (strpos($ua, 'edg') !== false) {
        $browser = 'Edge';
    } elseif (strpos($ua, 'micromessenger') !== false) {
        $browser = 'WeChat';
    } elseif (strpos($ua, 'qq/') !== false) {
        $browser = 'QQ';
    }

    // 操作系统
    if (strpos($ua, 'windows') !== false) {
        $os = 'Windows';
    } elseif (strpos($ua, 'mac') !== false) {
        $os = 'macOS';
    } elseif (strpos($ua, 'linux') !== false) {
        $os = 'Linux';
    } elseif (strpos($ua, 'android') !== false) {
        $os = 'Android';
    } elseif (strpos($ua, 'iphone') !== false || strpos($ua, 'ipad') !== false) {
        $os = 'iOS';
    }

    return compact('device', 'browser', 'os');
}

// 主逻辑
try {
    $db = new StatsDB();
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'collect':
            // 接收前端上报
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST ?: [];
            }

            // Cloudflare真实IP（优先）
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '';
            if (!$ip) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
                if ($ip) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
            }
            if (!$ip) {
                $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            }

            // Cloudflare国家代码
            $countryCode = $_SERVER['HTTP_CF_IPCOUNTRY'] ?? 'XX';

            // 解析UA
            $ua = $input['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? '');
            $deviceInfo = parseUserAgent($ua);

            $data = [
                'ip' => $ip,
                'country_code' => $countryCode,
                'country_name' => getCountryName($countryCode),
                'user_agent' => $ua,
                'device_type' => $deviceInfo['device'],
                'browser' => $deviceInfo['browser'],
                'os' => $deviceInfo['os'],
                'referer' => $input['referer'] ?? ($_SERVER['HTTP_REFERER'] ?? ''),
                'page_url' => $input['page_url'] ?? ($_SERVER['HTTP_REFERER'] ?? ''),
                'cf_ray' => $_SERVER['HTTP_CF_RAY'] ?? null,
            ];

            $db->recordVisit($data);
            echo json_encode(['status' => 'ok']);
            break;

        case 'overview':
            // 概览数据
            $today = $db->getTodayStats();
            $total = $db->getTotalStats();
            echo json_encode([
                'today' => $today,
                'total' => $total,
            ]);
            break;

        case 'countries':
            // 国家分布
            $limit = intval($_GET['limit'] ?? 10);
            echo json_encode($db->getCountryDistribution($limit));
            break;

        case 'devices':
            // 设备分布
            echo json_encode($db->getDeviceDistribution());
            break;

        case 'browsers':
            // 浏览器分布
            echo json_encode($db->getBrowserDistribution());
            break;

        case 'recent':
            // 最近访问
            $limit = intval($_GET['limit'] ?? 50);
            echo json_encode($db->getRecentVisits($limit));
            break;

        case 'trend':
            // 趋势
            $days = intval($_GET['days'] ?? 7);
            echo json_encode($db->getTrend($days));
            break;

        default:
            http_response_code(404);
            echo json_encode(['error' => 'Unknown action']);
    }

} catch (Exception $e) {
    error_log('Stats API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal error']);
}
