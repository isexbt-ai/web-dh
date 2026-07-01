<?php
/**
 * redirect/api/stats.php - 跳转页统计API
 * 将统计数据写入主数据库 redirect_visits 表
 */

require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'collect':
        // 接收前端上报
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST ?: [];
        }

        // 获取真实IP
        $ip = getClientIp();

        // Cloudflare国家代码
        $countryCode = $_SERVER['HTTP_CF_IPCOUNTRY'] ?? 'XX';

        // 解析UA
        $ua = $input['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? '');
        $deviceInfo = parseUserAgent($ua);

        // 插入数据库
        try {
            $stmt = $pdo->prepare("INSERT INTO redirect_visits
                (ip, country_code, country_name, user_agent, device_type, browser, os, referer, page_url, cf_ray, created_at)
                VALUES (:ip, :cc, :cn, :ua, :device, :browser, :os, :referer, :page, :ray, :time)");

            $stmt->execute([
                ':ip' => $ip,
                ':cc' => $countryCode,
                ':cn' => getCountryName($countryCode),
                ':ua' => substr($ua, 0, 500),
                ':device' => $deviceInfo['device'] ?? 'unknown',
                ':browser' => $deviceInfo['browser'] ?? 'unknown',
                ':os' => $deviceInfo['os'] ?? 'unknown',
                ':referer' => $input['referer'] ?? ($_SERVER['HTTP_REFERER'] ?? ''),
                ':page' => $input['page_url'] ?? ($_SERVER['HTTP_REFERER'] ?? ''),
                ':ray' => $_SERVER['HTTP_CF_RAY'] ?? null,
                ':time' => time()
            ]);

            echo json_encode(['status' => 'ok']);
        } catch (PDOException $e) {
            error_log('Redirect stats error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Database error']);
        }
        break;

    case 'overview':
        // 概览数据
        try {
            $today = date('Y-m-d');
            $start = strtotime($today . ' 00:00:00');

            $stmt = $pdo->query("SELECT
                COUNT(*) as total,
                COUNT(DISTINCT ip) as unique_ips,
                SUM(CASE WHEN device_type = 'mobile' THEN 1 ELSE 0 END) as mobile,
                SUM(CASE WHEN device_type = 'desktop' THEN 1 ELSE 0 END) as desktop,
                SUM(CASE WHEN device_type = 'tablet' THEN 1 ELSE 0 END) as tablet
                FROM redirect_visits WHERE created_at >= $start");
            $todayData = $stmt->fetch();

            $stmt = $pdo->query("SELECT COUNT(*) as total, COUNT(DISTINCT ip) as unique_ips FROM redirect_visits");
            $totalData = $stmt->fetch();

            echo json_encode([
                'today' => $todayData,
                'total' => $totalData
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error']);
        }
        break;

    case 'countries':
        // 国家分布
        $limit = intval($_GET['limit'] ?? 10);
        try {
            $stmt = $pdo->query("SELECT country_name, country_code, COUNT(*) as count
                FROM redirect_visits WHERE country_code IS NOT NULL AND country_code != 'XX'
                GROUP BY country_code ORDER BY count DESC LIMIT $limit");
            echo json_encode($stmt->fetchAll());
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error']);
        }
        break;

    case 'devices':
        // 设备分布
        try {
            $stmt = $pdo->query("SELECT device_type, COUNT(*) as count
                FROM redirect_visits GROUP BY device_type ORDER BY count DESC");
            echo json_encode($stmt->fetchAll());
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error']);
        }
        break;

    case 'browsers':
        // 浏览器分布
        try {
            $stmt = $pdo->query("SELECT browser, COUNT(*) as count
                FROM redirect_visits GROUP BY browser ORDER BY count DESC LIMIT 10");
            echo json_encode($stmt->fetchAll());
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error']);
        }
        break;

    case 'recent':
        // 最近访问
        $limit = intval($_GET['limit'] ?? 50);
        try {
            $stmt = $pdo->prepare("SELECT * FROM redirect_visits ORDER BY created_at DESC LIMIT ?");
            $stmt->execute([$limit]);
            $data = $stmt->fetchAll();
            foreach ($data as &$row) {
                $row['created_at'] = date('Y-m-d H:i:s', $row['created_at']);
            }
            echo json_encode($data);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error']);
        }
        break;

    case 'trend':
        // 趋势
        $days = intval($_GET['days'] ?? 7);
        try {
            $data = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $start = strtotime($date . ' 00:00:00');
                $end = $start + 86400;

                $stmt = $pdo->query("SELECT COUNT(*) as count, COUNT(DISTINCT ip) as unique_ips
                    FROM redirect_visits WHERE created_at >= $start AND created_at < $end");
                $row = $stmt->fetch();
                $data[] = [
                    'date' => $date,
                    'count' => (int)$row['count'],
                    'unique_ips' => (int)$row['unique_ips']
                ];
            }
            echo json_encode($data);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error']);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Unknown action']);
}

/**
 * 解析User-Agent
 */
function parseUserAgent($ua) {
    $device = 'desktop';
    $browser = 'unknown';
    $os = 'unknown';

    if (empty($ua)) return compact('device', 'browser', 'os');
    $ua = strtolower($ua);

    if (strpos($ua, 'mobile') !== false || strpos($ua, 'android') !== false || strpos($ua, 'iphone') !== false) {
        $device = 'mobile';
    } elseif (strpos($ua, 'tablet') !== false || strpos($ua, 'ipad') !== false) {
        $device = 'tablet';
    }

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

/**
 * 获取国家名称
 */
function getCountryName($code) {
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
    return $countryMap[$code] ?? '未知(' . $code . ')';
}
