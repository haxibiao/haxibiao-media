<?php

function hash_vod_url($path)
{
    $path = ltrim($path, '/');
    return "http://hashvod-1251052432.file.myqcloud.com/" . $path;
}

function media_path($path)
{
    return __DIR__ . "/../../" . $path;
}

/**
 * media的laravel mix pack后的资源地址
 */
function media_mix($path)
{
    //FIXME: 支持version 变化
    return url("/vendor/media/" . $path);
}
