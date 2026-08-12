<?php

declare(strict_types=1);

return [
    'site_title' => '美女导航',
    'site_description' => '精选优质网站导航',
    'site_keywords' => '美女导航,网址导航,精选网站',
    'og_image' => '/assets/images/logo.png',
    'twitter_card' => 'summary',
    'domain' => $_ENV['SITE_DOMAIN'] ?? 'dh.xlbk.blog',
    'jsonld_type' => 'WebSite',
];
