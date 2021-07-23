<?php

namespace Haxibiao\Media;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;

class MediaTrack extends Model
{
    use HasFactory;

    protected $guarded = [];

    public static function recrod($model, $startTime, $endTime, $userId = 0, $uuid = '')
    {
        $startTime    = is_string($startTime) ? Carbon::parse($startTime) : $startTime;
        $endTime      = is_string($endTime) ? Carbon::parse($endTime) : $endTime;
        $trackSeconds = $endTime > $startTime ? $endTime->diffInSeconds($startTime) : 0;
        return self::create([
            'media_type'    => $model->getMorphClass(),
            'media_id'      => $model->id,
            'start_time'    => $startTime->toDateTimeString(),
            'end_time'      => $endTime->toDateTimeString(),
            'user_id'       => $userId,
            'track_seconds' => $trackSeconds,
            'uuid'          => $uuid,
        ]);
    }

    public static function resolveMediaTrackReport($root, $args, $context, $info)
    {
        $modelClass = Relation::getMorphedModel($args['media_type']);
        $model      = $modelClass::find($args['media_id']);
        $userId     = data_get(getUser(false), 'id', 0);
        if ($model) {
            return MediaTrack::recrod($model, $args['start_time'], $args['end_time'], $userId);
        }
    }
}