<?php

declare(strict_types=1);

namespace App;

use App\Database\Migrations;
use App\Middleware\ErrorMiddleware;
use App\Middleware\SecurityHeadersMiddleware;
use App\Middleware\SessionMiddleware;
use App\Middleware\VisitLogMiddleware;
use App\Service\View;
use App\Service\VisitService;
use App\Support\Config;
use App\Support\Env;
use PDO;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Factory\AppFactory;

/**
 * 应用引导：加载 .env/配置 → 构建容器 → 注册中间件与路由 → 返回 Slim App。
 */
final class Bootstrap
{
    public static function create(): App
    {
        Env::load(base_path('.env'));
        Config::load(base_path('config'));
        date_default_timezone_set((string) config('app.timezone', 'Asia/Shanghai'));

        /** @var ContainerInterface $container */
        $container = Container::build();

        // 幂等迁移：建表 + 补列 + 默认数据（CREATE IF NOT EXISTS，不触碰现有数据）
        Migrations::run($container->get(PDO::class));

        AppFactory::setContainer($container);
        $app = AppFactory::create();

        // 中间件（先 add 的在内层；ErrorMiddleware 放最外层以兜底一切异常）
        $app->addBodyParsingMiddleware();
        $app->add(new SessionMiddleware());
        $app->add(new SecurityHeadersMiddleware());
        $app->add(new VisitLogMiddleware($container->get(VisitService::class)));
        $app->add(new ErrorMiddleware($container->get(View::class), (bool) config('app.debug', false)));

        // 路由
        $routes = require base_path('routes/web.php');
        $routes($app);

        return $app;
    }
}
