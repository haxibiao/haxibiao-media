<?php

return [
    'hook'                           => env('MEDIA_HOOK'), //'http://datizhuanqian.com/api/media/hook'
    'spider'                         => [
        'enable'                              => env('MEDIA_SPIDER_ENABLE', true),
        'user_daily_spider_parse_limit_count' => env('USER_DAILY_SPIDER_PARSE_LIMIT_COUNT', -1), // 用户每日可抓取最大次数 -1:无限制
    ],

    /**
     * 电影模块配置
     */
    'movie'                          => [
        'enable'     => env('ENABLE_MOVIE', true),
        'middleware' => [
            'web',
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

    'image'                          => [
        'auto_cut' => false,
    ],

    /**
     * Movie表自增ID的起始位置
     * https://pm.haxifang.com/browse/GC-174
     */
    'movie_auto_increment__start_id' => env('MOVIE_AUTO_INCREMENT_START_ID',null),
];
