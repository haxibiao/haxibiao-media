<?php

namespace haxibiao\media\Traits;

use haxibiao\media\Spider;
use Illuminate\Support\Arr;

trait SpiderResolvers
{
    public function resolveGetSpiders($root, $args, $context, $info)
    {
        $type = Arr::get($args, 'type', null);
        return Spider::getSpiders(getUser(), $type, $args['limit'], $args['offset']);
    }

    public function resolveShareLink($root, $args, $context, $info)
    {
        return Spider::resolveDouyinVideo(getUser(), $args['share_link']);
    }
}
