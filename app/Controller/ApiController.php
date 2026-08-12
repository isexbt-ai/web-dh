<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\Card;
use App\Model\Message;
use App\Service\SettingService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * 公开 API：切换分类拉卡片 / 点击计数 / 留言列表与提交（含 IP 防刷）。
 */
final class ApiController
{
    public function __construct(
        private Card $cardModel,
        private Message $messageModel,
        private SettingService $settings
    ) {
    }

    public function cards(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $categoryId = isset($params['category_id']) && $params['category_id'] !== ''
            ? (int) $params['category_id']
            : null;
        $sortMethod = $this->settings->get('card_sort_method', 'default');

        $cards = $this->cardModel->getByCategory($categoryId, true, $sortMethod);
        $data = array_map(static fn (array $c): array => [
            'id' => (int) $c['id'],
            'title' => (string) $c['title'],
            'image' => (string) ($c['image'] ?? ''),
            'link' => (string) ($c['link'] ?? '#'),
            'detail' => (string) ($c['detail'] ?? ''),
            'card_type' => (string) ($c['card_type'] ?? 'link'),
            'badge_text' => (string) ($c['badge_text'] ?? ''),
            'image_width' => (int) ($c['image_width'] ?? 0),
            'image_height' => (int) ($c['image_height'] ?? 0),
        ], $cards);

        return $this->json($response, ['ok' => true, 'cards' => $data]);
    }

    public function click(Request $request, Response $response): Response
    {
        $body = (array) json_decode((string) $request->getBody(), true);
        $id = (int) ($body['card_id'] ?? 0);
        if ($id <= 0) {
            return $this->json($response, ['ok' => false, 'message' => '参数错误'], 400);
        }

        $key = 'api_card_clicked_' . $id;
        if (empty($_SESSION[$key])) {
            $this->cardModel->incrementClick($id);
            $_SESSION[$key] = true;
        }
        return $this->json($response, ['ok' => true]);
    }

    public function messages(Request $request, Response $response): Response
    {
        if ($request->getMethod() === 'POST') {
            return $this->submitMessage($request, $response);
        }
        return $this->listMessages($request, $response);
    }

    private function listMessages(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = max(1, (int) config('app.guestbook_per_page', 10));

        $messages = $this->messageModel->getApproved(($page - 1) * $perPage, $perPage);
        $data = array_map(static fn (array $m): array => [
            'id' => (int) $m['id'],
            'nickname' => (string) ($m['nickname'] ?? ''),
            'content' => (string) $m['content'],
            'reply' => (string) ($m['reply'] ?? ''),
            'created_at' => (string) $m['created_at'],
        ], $messages);

        return $this->json($response, ['ok' => true, 'messages' => $data, 'total' => $this->messageModel->countApproved()]);
    }

    private function submitMessage(Request $request, Response $response): Response
    {
        if ($this->settings->get('guestbook_enabled', '1') !== '1') {
            return $this->json($response, ['ok' => false, 'message' => '留言板已关闭'], 403);
        }

        $body = (array) json_decode((string) $request->getBody(), true);
        $nickname = mb_substr(trim((string) ($body['nickname'] ?? '')), 0, 20);
        $content = trim((string) ($body['content'] ?? ''));

        if ($content === '') {
            return $this->json($response, ['ok' => false, 'message' => '留言内容不能为空'], 422);
        }
        if (mb_strlen($content) > 500) {
            return $this->json($response, ['ok' => false, 'message' => '留言内容不能超过500字'], 422);
        }

        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        if ($ip !== '' && $this->messageModel->countRecentByIp($ip, 1) > 0) {
            return $this->json($response, ['ok' => false, 'message' => '操作太频繁，请稍后再试'], 429);
        }

        $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
        $id = $this->messageModel->create($nickname, $content, $ip, mb_substr($ua, 0, 255));
        return $this->json($response, ['ok' => true, 'id' => $id]);
    }

    private function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write((string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus($status);
    }
}
