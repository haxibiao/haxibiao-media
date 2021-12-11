<?php

return [
    //API
    'api'         => [
        'neihancloud' => env('API_NEIHANCLOUD', 'https://neihancloud.com'),
    ],

    //所有开关配置
    'enable'      => [
        //抖音爬虫
        'spider'     => env('MEDIA_SPIDER_ENABLE', true),
        //自动裁剪图片
        'auto_cut'   => false,
        //mediachain共享模式
        'mediachain' => env('ENABLE_MEDIACHAIN', false),
        //统计VOD视频的播放量?
        'vod'        => false,
        //电影模块开关
        'movie'      => env('ENABLE_MOVIE', false),
        //长视频slug
        'slug'       => env('ENABLE_MOVIE_SLUG', false),
    ],

    //长视频模块配置
    'movie'       => [
        'middleware' => [
            'web',
            'movie',
        ],
    ],

    //视频模块配置
    'video'       => [
        'middleware' => [
            'web',
        ],
    ],

    //分享任务检查外链？
    'chrome_port' => 'http://localhost:4444',

    //搜索
    'meilisearch' => [
        'index'  => env('APP_NAME'),
        'enable' => env('ENABLE_MEILISEARCH', false),
        'host'   => env('MEILISEARCH_HOST'),
        'key'    => env('MEILISEARCH_KEY'),
    ],
];
