<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Article;
use App\Model\Card;

/**
 * 全站搜索服务：跨卡片与文章聚合结果，按类型分组返回。
 * 只查启用状态的内容，关键词由 Model 层参数绑定。
 */
final class SearchService
{
    private const CARD_LIMIT = 20;
    private const ARTICLE_LIMIT = 8;
    private const MAX_KEYWORD_LENGTH = 50;

    public function __construct(
        private Card $cardModel,
        private Article $articleModel
    ) {
    }

    /**
     * 执行全站搜索。
     *
     * @return array{keyword: string, total: int, cards: array<int, array<string, mixed>>, articles: array<int, array<string, mixed>>}
     */
    public function search(string $keyword): array
    {
        $keyword = trim($keyword);
        if (mb_strlen($keyword) > self::MAX_KEYWORD_LENGTH) {
            $keyword = mb_substr($keyword, 0, self::MAX_KEYWORD_LENGTH);
        }

        $cards = [];
        $articles = [];
        if ($keyword !== '') {
            foreach ($this->cardModel->search($keyword, self::CARD_LIMIT) as $row) {
                $cards[] = [
                    'id' => (int) $row['id'],
                    'title' => (string) $row['title'],
                    'category' => (string) ($row['category_name'] ?? ''),
                    'image' => (string) ($row['image'] ?? ''),
                    'link' => (string) ($row['link'] ?? ''),
                    'card_type' => (string) ($row['card_type'] ?? 'link'),
                    'detail' => (string) ($row['detail'] ?? ''),
                ];
            }
            foreach ($this->articleModel->search($keyword, self::ARTICLE_LIMIT) as $row) {
                $articles[] = [
                    'id' => (int) $row['id'],
                    'title' => (string) $row['title'],
                    'summary' => mb_substr((string) ($row['summary'] ?? ''), 0, 120),
                    'image' => (string) ($row['cover_image'] ?? ''),
                    'url' => '/article/' . (int) $row['id'] . '-' . (string) $row['slug'] . '.html',
                    'created_at' => (string) ($row['created_at'] ?? ''),
                ];
            }
        }

        return [
            'keyword' => $keyword,
            'total' => count($cards) + count($articles),
            'cards' => $cards,
            'articles' => $articles,
        ];
    }
}
