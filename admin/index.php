<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 检查是否被锁定
    if (isLoginLocked()) {
        $remaining = 900 - (time() - ($_SESSION['login_lock_time'] ?? 0));
        $minutes = ceil($remaining / 60);
        $error = "登录失败次数过多，请{$minutes}分钟后再试";
    } elseif (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = '安全验证失败，请刷新页面重试';
    } else {
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        if (verifyLogin($username, $password)) {
            resetLoginAttempts();
            doLogin($username);
            header('Location: dashboard.php');
            exit;
        } else {
            recordLoginFailure();
            $attemptsLeft = 5 - ($_SESSION['login_attempts'] ?? 0);
            if ($attemptsLeft > 0) {
                $error = "用户名或密码错误，还可尝试{$attemptsLeft}次";
            } else {
                $error = '登录失败次数过多，请15分钟后再试';
            }
        }
    }
}

// 如果已登录，跳转到仪表盘
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台登录 - <?php echo e(getConfig('site_title', '美女导航')); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .login-title {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-title h1 {
            font-size: 24px;
            color: #fff;
            margin-bottom: 8px;
        }

        .login-title p {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.5);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.8);
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            font-size: 15px;
            outline: none;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: #e94560;
            box-shadow: 0 0 0 3px rgba(233, 69, 96, 0.2);
        }

        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }

        .error-message {
            background: rgba(233, 69, 96, 0.2);
            border: 1px solid rgba(233, 69, 96, 0.3);
            color: #ff6b6b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }

        .login-btn {
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            border: none;
            background: linear-gradient(135deg, #e94560, #ff6b6b);
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(233, 69, 96, 0.4);
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: rgba(255, 255, 255, 0.5);
            font-size: 14px;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .back-link a:hover {
            color: #e94560;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-title">
            <h1>后台管理</h1>
            <p>请输入您的账号密码登录</p>
        </div>

        <?php if ($error): ?>
        <div class="error-message"><?php echo e($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <div class="form-group">
                <label for="username">用户名</label>
                <input type="text" id="username" name="username" placeholder="请输入用户名" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">密码</label>
                <input type="password" id="password" name="password" placeholder="请输入密码" required>
            </div>
            <button type="submit" class="login-btn">登录</button>
        </form>

        <div class="back-link">
            <a href="../">返回首页</a>
        </div>
    </div>
</body>
</html>
