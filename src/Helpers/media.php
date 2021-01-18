<?php

function hash_vod_url($path)
{
    $path = ltrim($path, '/');
    return "http://hashvod-1251052432.file.myqcloud.com/" . $path;
}
