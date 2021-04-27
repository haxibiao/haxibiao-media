<?php

// 目前breeze安装支持的2种云磁盘
return [
    'cos'   => [
        'driver'          => 'cosv5',
        'region'          => env('COS_REGION', 'ap-guangzhou'),
        'bucket'          => env('COS_BUCKET'),
        'credentials'     => [
            'appId'     => env('COS_APP_ID'),
            'secretId'  => env('COS_SECRET_ID'),
            'secretKey' => env('COS_SECRET_KEY'),
        ],
        'timeout'         => 60,
        'connect_timeout' => 60,
        'cdn'             => "https://" . env('COS_DOMAIN'),
        'scheme'          => 'https',
        'read_from_cdn'   => true, //默认从cdn读取，预热
        'cdn_key'         => null,
        'encrypt'         => false,
        'disable_asserts' => true,
    ],
    'space' => [
        'driver'     => 's3',
        'region'     => env('SPACE_REGION', 'sfo2'),
        'bucket'     => env('SPACE_BUCKET', 'movieimage'),
        'key'        => env('SPACE_KEY'),
        'secret'     => env('SPACE_SECRET'),
        'url'        => "https://" . env('SPACE_DOMAIN'),
        'endpoint'   => 'https://' . env('SPACE_REGION') . '.digitaloceanspaces.com',
        'visibility' => 'public',
    ],
];
