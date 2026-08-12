<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Setting;

/**
 * 站点配置服务：读写 site_config 键值表，批量写支持白名单。
 */
final class SettingService
{
    public function __construct(private Setting $model)
    {
    }

    public function get(string $key, string $default = ''): string
    {
        return $this->model->get($key, $default);
    }

    public function set(string $key, string $value): void
    {
        $this->model->set($key, $value);
    }

    /** 批量写入配置；白名单为空时不过滤（调用方负责校验来源）。 */
    public function setMany(array $configs, array $whitelist = []): void
    {
        if ($whitelist !== []) {
            $configs = array_intersect_key($configs, array_flip($whitelist));
        }
        foreach ($configs as $key => $value) {
            $this->set($key, (string) $value);
        }
    }

    public function all(): array
    {
        return $this->model->all();
    }
}
