<?php

namespace Haxibiao\Media\Traits;

use Haxibiao\Media\Movie;
use Haxibiao\Media\SearchLog;

trait SearchLogRepo
{
    public static function saveSearchLog($query, $userId = null, $type = "movies")
    {
        // 保存搜索记录
        $log = SearchLog::firstOrNew([
            'user_id' => $userId,
            'keyword' => $query,
            'type'    => $type,
        ]);
        if (isset($log->id)) {
            $log->increment('count');
        }
        // 如果有完全匹配的作品名字
        if ($type == "movies") {
            if ($movie = Movie::where('name', $query)->orderBy('id')->first()) {
                $log->movie_type   = $movie->type_name;
                $log->movie_reigon = $movie->country;
            }
        }

        $log->save();

        return $log;
    }
}
