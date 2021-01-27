<?php

return [
    //配置cos
    'cos' => [
        'driver'          => 'cosv5',
        'region'          => env('COS_REGION', 'ap-guangzhou'),
        'credentials'     => [
            'appId'     => env('COS_APP_ID'),
            'secretId'  => env('COS_SECRET_ID'),
            'secretKey' => env('COS_SECRET_KEY'),
        ],
        'timeout'         => env('COS_TIMEOUT', 60),
        'connect_timeout' => env('COS_CONNECT_TIMEOUT', 60),
        'bucket'          => env('COS_BUCKET'),
        'cdn'             => "http://" . env('COS_DOMAIN'),
        'scheme'          => env('COS_SCHEME', 'http'),
        'read_from_cdn'   => env('COS_READ_FROM_CDN', false),
        'cdn_key'         => env('COS_CDN_KEY'),
        'encrypt'         => env('COS_ENCRYPT', false),
        'disable_asserts' => true,
    ],
];
