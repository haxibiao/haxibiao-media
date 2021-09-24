<?php

return [
    //第三方API
    'api'                            => [
        'neihancloud' => env('API_NEIHANCLOUD', 'https://neihancloud.com'),
    ],
    'spider'                         => [
        'enable'                              => env('MEDIA_SPIDER_ENABLE', true),
        'user_daily_spider_parse_limit_count' => env('USER_DAILY_SPIDER_PARSE_LIMIT_COUNT', -1), // 用户每日可抓取最大次数 -1:无限制
    ],

    /**
     * 电影模块配置
     */
    'movie'                          => [
        'enable'     => env('ENABLE_MOVIE', false),
        'middleware' => [
            'web',
			'movie'
        ],
    ],
    /**
     * 视频模块配置
     */
    'video'                          => [
        'middleware' => [
            'web',
        ],
    ],

    /**
     * 是否统计视频的播放量
     */
    'enabled_statistics_video_views' => false,

    /**
     * 是否自动裁剪图片（需要imagick）
     */
    'image'                          => [
        'auto_cut' => false,
    ],

    'meilisearch'                    => [
        'index'  => env('APP_NAME'),
        'enable' => env('ENABLE_MEILISEARCH', false),
        'host'   => env('MEILISEARCH_HOST'),
        'key'    => env('MEILISEARCH_KEY'),
    ],

    /**
     * 是否开启本地mediachain共享movies
     */
    'enable_mediachain'              => env('ENABLE_MEDIACHAIN', false),

    /**
     * Movie表自增ID的起始位置
     */
    'movie_start_id'                 => env('MOVIE_START_ID', null),
];
