<?php

declare(strict_types=1);

/**
 * 创建/更新后台管理员（首次部署用）
 * 用法: php tools/create_admin.php <用户名> <密码> [role]
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Support\Config;
use App\Support\Env;
use PDO;

Env::load(base_path('.env'));
Config::load(base_path('config'));

if ($argc < 3) {
    echo "用法: php tools/create_admin.php <用户名> <密码> [role]\n";
    echo "  role 可选: admin / superadmin（superadmin 可改站点配置/删除数据，默认 admin）\n";
    exit(1);
}

$username = trim($argv[1]);
$password = $argv[2];
$role = $argv[3] ?? 'admin';

if ($username === '' || mb_strlen($password) < 6) {
    echo "❌ 用户名不能为空，密码至少 6 位\n";
    exit(1);
}

$pdo = new PDO('sqlite:' . (string) config('database.path'));
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare('SELECT id FROM admin_users WHERE username = ?');
$stmt->execute([$username]);

if ($stmt->fetch() !== false) {
    $upd = $pdo->prepare('UPDATE admin_users SET password = ?, role = ? WHERE username = ?');
    $upd->execute([$hash, $role, $username]);
    echo "✅ 已更新管理员: {$username}（role={$role}）\n";
} else {
    $ins = $pdo->prepare('INSERT INTO admin_users (username, password, role) VALUES (?, ?, ?)');
    $ins->execute([$username, $hash, $role]);
    echo "✅ 已创建管理员: {$username}（role={$role}）\n";
}
