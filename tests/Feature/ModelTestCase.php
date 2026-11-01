<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Database\Migrations;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * 功能测试基类：提供内存数据库与建表。
 * 每个测试用独立的内存库，互不干扰，也不触碰 data/nav.db。
 */
abstract class ModelTestCase extends TestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        Migrations::run($this->pdo);
    }
}
