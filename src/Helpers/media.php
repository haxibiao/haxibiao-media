<?php

use Haxibiao\Media\MovieHistory;
use Illuminate\Support\Str;

/**
 * media的mix
 */
function media_mix($path)
{
    //启用jsdelivr的cdn加速
    if (is_enable_jsdelivr()) {
        //用压缩版本的
        $asset_path = ends_with('.min.js', $path) ? $path : str_replace('.js', '.min.js', $path);
        $asset_path = ends_with('.min.css', $path) ? $path : str_replace('.css', '.min.css', $path);

        $tag = "0.0.3";
        return "https://cdn.jsdelivr.net/gh/haxibiao/haxibiao-media@" . $tag . "/public" . $asset_path;
    }
    return breeze_mix($path);
}

function is_enable_mediachain()
{
    return config('media.enable.mediachain', false);
}

function get_neihancloud_api()
{
    return config('media.api.neihancloud', 'https://neihancloud.com');
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
