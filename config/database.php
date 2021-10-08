<?php
return [
    //内涵云(长视频分布式节点，建议用本地容器/服务器最佳)
    'mediachain' => [
        'driver'         => 'mysql',
        'url'            => env('DATABASE_URL'),
        'host'           => env('DB_MEDIACHAIN_HOST', env('DB_HOST')),
        'port'           => env('DB_MEDIACHAIN_PORT', env('DB_PORT', 3306)),
        'database'       => env('DB_MEDIACHAIN_DATABASE', 'mediachain'),
        'username'       => env('DB_MEDIACHAIN_USERNAME', env('DB_USERNAME', 'root')),
        'password'       => env('DB_MEDIACHAIN_PASSWORD', env('DB_PASSWORD')),
        'unix_socket'    => env('DB_SOCKET', ''),
        'charset'        => 'utf8mb4',
        'collation'      => 'utf8mb4_unicode_ci',
        'prefix'         => '',
        'prefix_indexes' => true,
        'strict'         => true,
        'engine'         => null,
        'options'        => extension_loaded('pdo_mysql') ? array_filter([
            PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
        ]) : [],
    ],
    //哈希云(短视频分布式节点，建议用本地容器/服务器最佳)
    'media'      => [
        'driver'         => 'mysql',
        'url'            => env('DATABASE_URL'),
        'host'           => env('DB_MEDIA_HOST', env('DB_HOST')),
        'port'           => env('DB_MEDIA_PORT', env('DB_PORT', 3306)),
        'database'       => env('DB_MEDIA_DATABASE', 'media'),
        'username'       => env('DB_MEDIA_USERNAME', env('DB_USERNAME', 'root')),
        'password'       => env('DB_MEDIA_PASSWORD', env('DB_PASSWORD')),
        'unix_socket'    => env('DB_SOCKET', ''),
        'charset'        => 'utf8mb4',
        'collation'      => 'utf8mb4_unicode_ci',
        'prefix'         => '',
        'prefix_indexes' => true,
        'strict'         => true,
        'engine'         => null,
        'options'        => extension_loaded('pdo_mysql') ? array_filter([
            PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
        ]) : [],
    ],
];
