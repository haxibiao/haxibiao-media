<?php

use Haxibiao\Media\MovieHistory;
use Illuminate\Support\Str;

function get_neihancloud_api()
{
    return config('media.api.neihancloud', 'https://mediachain.info');
}

function hash_vod_url($path)
{
    $path = ltrim($path, '/');
    return "http://hashvod-1251052432.file.myqcloud.com/" . $path;
}

/**
 * 格式化电影路由
 * https://pm.haxifang.com/browse/HXB-134
 */
function movie_url($url, $index, $value)
{
    $basename   = basename($url);
    $parameters = explode('-', $basename);
    $new        = data_set($parameters, $index, $value);
    $new        = implode('-', $new);
    return Str::replaceFirst($basename, $new, $url);
}

if (!function_exists('media_path')) {
    function media_path($path)
    {
        return __DIR__ . "/../../" . $path;
    }
}

//用户播放过的影片记录 movie.header 用
function userPlayedMovies($take = 10)
{
    if ($user_id = getUserId()) {
        return MovieHistory::userPlayedMovies($user_id, $take);
    }
    return [];
}
