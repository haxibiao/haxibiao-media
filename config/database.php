<?php
return [
    'mediachain'         => [
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
];
