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
 * 检查是否已登录
 */
function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
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
 * 检查是否被锁定（5次失败后锁定15分钟）
 */
function isLoginLocked() {
    $attempts = $_SESSION['login_attempts'] ?? 0;
    $lockTime = $_SESSION['login_lock_time'] ?? 0;

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
 * 记录登录失败
 */
function recordLoginFailure() {
    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
    if ($_SESSION['login_attempts'] >= 5) {
        $_SESSION['login_lock_time'] = time();
    }
}

/**
 * 重置登录尝试计数
 */
function resetLoginAttempts() {
    unset($_SESSION['login_attempts']);
    unset($_SESSION['login_lock_time']);
}
