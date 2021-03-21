<?php

function hash_vod_url($path)
{
    $path = ltrim($path, '/');
    return "http://hashvod-1251052432.file.myqcloud.com/" . $path;
}

if (!function_exists('media_path')) {
    function media_path($path)
    {
        return __DIR__ . "/../../" . $path;
    }
}

/**
 * media的laravel mix pack后的资源地址
 */
function media_mix($path)
{
    $manifestPath = media_path('public/mix-manifest.json');
    return breeze_mix($path, $manifestPath);
}
