<?php
/**
 * 登录认证模块
 */

// 配置 session cookie 路径为根目录，确保跨子目录访问时 session 一致
ini_set('session.cookie_path', '/');
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
session_start();

/**
 * 检查是否已登录（包含 session 超时检查）
 * 默认超时时间：30分钟无操作自动登出
 */
function isLoggedIn() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        return false;
    }

    // 检查 session 是否超时（30分钟 = 1800秒）
    $lastActivity = $_SESSION['last_activity'] ?? 0;
    if ((time() - $lastActivity) > 1800) {
        // Session 已超时，执行登出
        doLogout();
        return false;
    }

    // 更新最后活动时间
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * 要求登录，未登录则跳转
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

/**
 * 验证登录凭据
 */
function verifyLogin($username, $password) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        return true;
    }
    return false;
}

/**
 * 执行登录
 */
function doLogin($username) {
    // 防止Session Fixation攻击：登录后重新生成session ID
    session_regenerate_id(true);

    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_username'] = $username;
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
}

/**
 * 执行登出
 */
function doLogout() {
    session_destroy();
    header('Location: index.php');
    exit;
}

/**
 * 生成CSRF Token
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * 验证CSRF Token
 */
function verifyCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * 检查是否被锁定（5次失败后锁定15分钟）- 基于 IP
 */
function isLoginLocked() {
    $ip = getClientIp();
    $key = 'login_lock_' . md5($ip);
    $attempts = $_SESSION[$key . '_attempts'] ?? 0;
    $lockTime = $_SESSION[$key . '_lock_time'] ?? 0;

    if ($attempts >= 5 && (time() - $lockTime) < 900) {
        return true;
    }

    // 锁定时间已过，重置
    if ($attempts >= 5 && (time() - $lockTime) >= 900) {
        resetLoginAttempts();
    }

    return false;
}

/**
 * 记录登录失败 - 基于 IP
 */
function recordLoginFailure() {
    $ip = getClientIp();
    $key = 'login_lock_' . md5($ip);
    $_SESSION[$key . '_attempts'] = ($_SESSION[$key . '_attempts'] ?? 0) + 1;
    if ($_SESSION[$key . '_attempts'] >= 5) {
        $_SESSION[$key . '_lock_time'] = time();
    }
}

/**
 * 重置登录尝试计数 - 基于 IP
 */
function resetLoginAttempts() {
    $ip = getClientIp();
    $key = 'login_lock_' . md5($ip);
    unset($_SESSION[$key . '_attempts']);
    unset($_SESSION[$key . '_lock_time']);
}
