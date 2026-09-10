<?php

declare(strict_types=1);

namespace App;

use DI\ContainerBuilder;
use App\Model\LoginAttempt;
use App\Model\Setting;
use App\Model\VisitStat;
use App\Service\CacheService;
use App\Service\RateLimitService;
use App\Service\R2Service;
use App\Service\SettingService;
use App\Service\UploadService;
use App\Service\View;
use App\Service\VisitService;
use PDO;
use Psr\Container\ContainerInterface;

/**
 * 依赖注入容器构建器（PHP-DI）。
 * Model 由 autowiring 自动解析（构造注入 PDO）；需要配置参数的 Service 在此显式注册。
 */
final class Container
{
    public static function build(): ContainerInterface
    {
        $builder = new ContainerBuilder();
        $builder->useAutowiring(true);
        $builder->useAnnotations(false);

        $builder->addDefinitions([
            PDO::class => function (): PDO {
                $path = (string) config('database.path');
                $dir = dirname($path);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                $pdo = new PDO('sqlite:' . $path);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                $pdo->exec('PRAGMA journal_mode=' . (string) config('database.journal_mode', 'WAL'));
                $pdo->exec('PRAGMA busy_timeout=' . (int) config('database.busy_timeout', 5000));
                return $pdo;
            },

            View::class => fn (ContainerInterface $c) => new View($c),

            CacheService::class => fn () => new CacheService(
                (string) config('app.cache_dir'),
                (int) config('app.cache_ttl', 300)
            ),
            SettingService::class => fn (ContainerInterface $c) => new SettingService($c->get(Setting::class)),
            VisitService::class => fn (ContainerInterface $c) => new VisitService(
                $c->get(VisitStat::class),
                $c->get(SettingService::class),
                $c->get(CacheService::class),
            ),
            RateLimitService::class => fn (ContainerInterface $c) => new RateLimitService(
                $c->get(LoginAttempt::class),
                (int) config('app.login_lock_attempts', 5),
                (int) config('app.login_lock_duration', 900)
            ),
            UploadService::class => fn () => new UploadService(
                (string) config('storage.upload_dir'),
                (string) config('storage.upload_url_base'),
                (int) config('app.upload_max_size', 50 * 1024 * 1024)
            ),
            R2Service::class => fn () => new R2Service((array) config('storage.r2', [])),
        ]);

        return $builder->build();
    }
}
