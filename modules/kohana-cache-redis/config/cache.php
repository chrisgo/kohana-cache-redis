<?php defined('SYSPATH') or die('No direct script access.');
return [
    'redis' => [
        'driver'                => 'redis',
        'default_expire'        => 3600,
        'cache_prefix'          => 'cache',
        'tag_prefix'            => '_tag',
        'servers' => [
            'local' => [
                //'host'          => 'unix:///var/run/redis/redis.sock',
                'host'          => 'localhost',
                'port'          => 6379,
                'persistent'    => FALSE,
                'prefix'        => '',
                'password'      => '',
            ],
        ],
    ]
];
