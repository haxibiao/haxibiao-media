<?php
return [
    //内涵云(长视频分布式节点，建议用本地容器/服务器最佳)
    'mediachain' => [
        'driver'         => 'mysql',
        'url'            => env('DATABASE_URL'),
        'host'           => env('DB_HOST_MEDIACHAIN', env('DB_HOST')),
        'port'           => env('DB_PORT_MEDIACHAIN', env('DB_PORT', 3306)),
        'database'       => env('DB_DATABASE_MEDIACHAIN', env('DB_DATABASE')),
        'username'       => 'root',
        'password'       => env('DB_PASSWORD_MEDIA', env('DB_PASSWORD')),
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
    //哈希云(短视频分布式节点，建议用本地容器/服务器最佳)
    'media'      => [
        'driver'         => 'mysql',
        'url'            => env('DATABASE_URL'),
        'host'           => env('DB_HOST_MEDIA', env('DB_HOST')),
        'port'           => env('DB_PORT_MEDIA', env('DB_PORT', 3306)),
        'database'       => env('DB_DATABASE_MEDIA', 'media'),
        'username'       => 'root',
        'password'       => env('DB_PASSWORD_MEDIA', env('DB_PASSWORD')),
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
