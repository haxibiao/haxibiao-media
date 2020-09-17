<?php

return [
    'hook'   => env('MEDIA_HOOK'), //'http://datizhuanqian.com/api/media/hook'
    'spider' => [
        'enable'                              => env('MEDIA_SPIDER_ENABLE', true),
        'user_daily_spider_parse_limit_count' => env('USER_DAILY_SPIDER_PARSE_LIMIT_COUNT', -1), // 用户每日可抓取最大次数 -1:无限制
    ],
];
