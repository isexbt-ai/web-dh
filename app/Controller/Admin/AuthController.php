<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\AdminUser;
use App\Service\RateLimitService;
use App\Service\View;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Psr7\Response as SlimResponse;

/**
 * 后台认证控制器：登录/登出/修改密码。
 * 登录失败按 IP 锁定（RateLimitService），登录成功 session_regenerate_id 防会话固定。
 */
final class AuthController
{
    public function __construct(
        private View $view,
        private AdminUser $adminUser,
        private RateLimitService $rateLimit
    ) {
    }

    public function loginForm(Request $request, Response $response): Response
    {
        $siteTitle = (string) config('seo.site_title', '后台管理');
        return $this->view->page($response, 'layouts/admin', 'admin/login', [
            'title' => '登录',
            'siteTitle' => $siteTitle,
            'active' => 'login',
        ]);
    }

    public function login(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody() ?: [];
        $username = trim((string) ($body['username'] ?? ''));
        $password = (string) ($body['password'] ?? '');
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

        if ($username === '' || $password === '') {
            return $this->json($response, false, '请输入用户名和密码');
        }
        if ($this->rateLimit->isLoginLocked($ip)) {
            return $this->json($response, false, '尝试次数过多，请 15 分钟后再试');
        }

        $user = $this->adminUser->findByUsername($username);
        if ($user === null || !password_verify($password, (string) $user['password'])) {
            $remaining = $this->rateLimit->recordLoginFailure($ip);
            $msg = $remaining > 0 ? "用户名或密码错误，还可尝试 $remaining 次" : '尝试次数过多，请 15 分钟后再试';
            return $this->json($response, false, $msg);
        }

        $this->rateLimit->resetLoginFailures($ip);
        session_regenerate_id(true);
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_role'] = (string) ($user['role'] ?? 'admin');

        return $this->json($response, true, '登录成功', '/admin');
    }

    public function logout(Request $request, Response $response): Response
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $res = new SlimResponse();
        return $res->withStatus(302)->withHeader('Location', '/admin/login');
    }

    public function passwordForm(Request $request, Response $response): Response
    {
        return $this->view->page($response, 'layouts/admin', 'admin/password', [
            'title' => '修改密码',
            'active' => 'password',
        ]);
    }

    public function password(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody() ?: [];
        $old = (string) ($body['old_password'] ?? '');
        $new = (string) ($body['new_password'] ?? '');
        $confirm = (string) ($body['confirm_password'] ?? '');
        $username = (string) ($_SESSION['admin_username'] ?? '');

        if (mb_strlen($new) < 6 || mb_strlen($new) > 128) {
            return $this->json($response, false, '新密码长度需为 6-128 位');
        }
        if ($new !== $confirm) {
            return $this->json($response, false, '两次输入的新密码不一致');
        }
        if ($old === $new) {
            return $this->json($response, false, '新密码不能与旧密码相同');
        }

        $user = $this->adminUser->findByUsername($username);
        if ($user === null || !password_verify($old, (string) $user['password'])) {
            return $this->json($response, false, '旧密码不正确');
        }

        $this->adminUser->updatePassword($username, password_hash($new, PASSWORD_DEFAULT));
        $this->logout($request, $response);
        return $this->json($response, true, '密码修改成功，请重新登录');
    }

    private function json(Response $response, bool $success, string $message, string $redirect = ''): Response
    {
        $response->getBody()->write((string) json_encode(
            ['success' => $success, 'message' => $message, 'redirect' => $redirect],
            JSON_UNESCAPED_UNICODE
        ));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
