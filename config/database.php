<?php
return [
    //FIXME: 急需修复的db pass暴露问题...
    'mediachain' => [
        'driver'         => 'mysql',
        'url'            => env('DATABASE_URL'),
        'host'           => 'hk008.haxibiao.com',
        'port'           => '3306',
        'database'       => 'mediachain',
        'username'       => 'root',
        'password'       => 'yp1qaz@WSX',
        'unix_socket'    => env('DB_SOCKET', ''),
        'charset'        => 'utf8mb4',
        'collation'      => 'utf8mb4_unicode_ci',
        'prefix'         => '',
        'prefix_indexes' => true,
        'strict'         => false,
        'engine'         => null,
        'options'        => [
            PDO::ATTR_PERSISTENT => true,
        ],
    ],
    'media'      => [
        'driver'         => 'mysql',
        'url'            => env('DATABASE_URL'),
        'host'           => 'media.haxibiao.com',
        // 'host'           => '127.0.0.1',
        'port'           => '3306',
        'database'       => 'media',
        'username'       => 'root',
        'password'       => 'yp1qaz@WSX',
        // 'password'       => 'localdb001',
        'unix_socket'    => env('DB_SOCKET', ''),
        'charset'        => 'utf8mb4',
        'collation'      => 'utf8mb4_unicode_ci',
        'prefix'         => '',
        'prefix_indexes' => true,
        'strict'         => false,
        'engine'         => null,
        'options'        => [
            PDO::ATTR_PERSISTENT => true,
        ],
    ],
];
