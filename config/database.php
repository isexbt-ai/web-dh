<?php

declare(strict_types=1);

return [
    'path' => dirname(__DIR__) . '/data/nav.db',
    'journal_mode' => 'WAL',
    'busy_timeout' => 5000,
    'charset' => 'utf8',
];
