<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\Gallery;
use App\Model\Showcase;
use App\Service\SeoService;
use App\Service\View;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;

/**
 * 效果展示控制器：全部列表 + 按相册筛选。
 */
final class ShowcaseController
{
    public function __construct(
        private View $view,
        private Showcase $showcaseModel,
        private Gallery $galleryModel,
        private SeoService $seo
    ) {
    }

    public function index(Request $request, Response $response): Response
    {
        $showcases = array_map(fn (array $it) => $this->withImage($it), $this->showcaseModel->getAll(true));
        $seo = $this->seo->page('效果展示', '', '/showcase');

        return $this->view->page($response, 'layouts/front', 'front/showcase', array_merge($seo, [
            'showcases' => $showcases,
            'galleries' => $this->galleryModel->getAll(true),
            'currentGalleryId' => 0,
        ]));
    }

    public function gallery(Request $request, Response $response, array $args): Response
    {
        $gid = (int) ($args['id'] ?? 0);
        $gallery = $this->galleryModel->find($gid);
        if ($gallery === null) {
            throw new HttpNotFoundException($request, '相册不存在');
        }

        $showcases = array_map(fn (array $it) => $this->withImage($it), $this->showcaseModel->getAll(true, $gid));
        $seo = $this->seo->page(
            (string) $gallery['title'] . ' - 效果展示',
            (string) ($gallery['description'] ?? ''),
            '/showcase/' . $gid . '.html'
        );

        return $this->view->page($response, 'layouts/front', 'front/showcase', array_merge($seo, [
            'showcases' => $showcases,
            'galleries' => $this->galleryModel->getAll(true),
            'currentGalleryId' => $gid,
        ]));
    }

    /** 图片地址解析：已迁移 R2 的走 imgbed_url，否则用本地 image。 */
    private function withImage(array $item): array
    {
        $item['image_url'] = (int) ($item['imgbed_status'] ?? 0) === 1 && !empty($item['imgbed_url'])
            ? (string) $item['imgbed_url']
            : (string) ($item['image'] ?? '');
        return $item;
    }
}
