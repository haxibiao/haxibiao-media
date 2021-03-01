<?php

namespace Haxibiao\Media\Traits;

use Haxibiao\Media\Danmu;
use Haxibiao\Media\Events\DanmuEvent;
use Haxibiao\Media\Movie;

trait DanmuResolvers
{
    public function sendDanmu($rootValue, array $args, $context, $resolveInfo)
    {
        if ($user = getUser()) {
            $movie = Movie::find($args['movie_id']);
            $danmu = Danmu::create([
                'user_id'     => $user->id,
                'movie_id'    => $movie->id,
                'series_name' => $args['series_name'],
                'content'     => $args['content'],
                'time'        => $args['time'] ?? null,
            ]);
            broadcast(new DanmuEvent($danmu, $movie->id, $args['series_name']));
            return $danmu;
        }
    }
}
