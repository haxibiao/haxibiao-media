<?php

namespace Haxibiao\Media\Traits;

use App\Series;
use Haxibiao\Media\Danmu;
use Haxibiao\Media\Events\DanmuEvent;

trait DanmuResolvers
{
    public function sendDanmu($rootValue, array $args, $context, $resolveInfo)
    {
        if ($user = getUser()) {
            $series = Series::find($args['series_id']);
            $danmu  = Danmu::create([
                'user_id'   => $user->id,
                'series_id' => $series->id,
                'content'   => $args['content'],
                'time'      => $args['time'],
            ]);
            broadcast(new DanmuEvent($danmu, $series->movie->id, $series->id));
            return $danmu;
        }
    }
}
