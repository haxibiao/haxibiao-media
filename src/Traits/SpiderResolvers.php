<?php

namespace haxibiao\media\Traits;

use haxibiao\media\Spider;
use Illuminate\Support\Arr;

trait SpiderResolvers
{
    public function resolveSpiders($root, $args, $context, $info)
    {
        $type = Arr::get($args, 'type', null);
        return Spider::querySpiders(getUser(), $type);
    }

    public function resolveShareLink($root, $args, $context, $info)
    {
        return Spider::resolveDouyinVideo(getUser(), $args['share_link']);
    }
}
