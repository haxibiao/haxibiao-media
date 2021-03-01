<?php

namespace Haxibiao\Media\Traits;

use Haxibiao\Media\Danmu;
use Haxibiao\Media\Events\DanmuEvent;

trait DanmuResolvers
{
    public function sendDanmu($rootValue, array $args, $context, $resolveInfo)
    {
        if ($user = getUser()) {
            $danmu = Danmu::create([
                'user_id'     => $user->id,
                'movie_id'    => $args['movie_id'],
                'series_name' => $args['series_name'],
                'content'     => $args['content'],
                'time'        => $args['time'] ?? null,
            ]);
            broadcast(new DanmuEvent($danmu, $args['movie_id'], $args['series_name']));
            return $danmu;
        }
    }
}
