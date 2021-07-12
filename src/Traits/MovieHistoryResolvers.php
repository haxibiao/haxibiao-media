<?php

namespace Haxibiao\Media\Traits;

use Haxibiao\Media\MovieHistory;
use Illuminate\Support\Facades\DB;

trait MovieHistoryResolvers
{
    //resolvers
    public static function saveWatchProgress($root, $args, $content, $info)
    {
        $movie_id     = data_get($args, 'movie_id');
        $series_index = data_get($args, 'series_index');
        $progress     = data_get($args, 'progress');
        app_track_event('长视频', '保存观看记录', '电影id:' . $movie_id);
        if ($user = currentUser()) {
            // 保存观看历史,存储每集的进度
            //$series_id,非series表主键，存储的值为series数组索引index
            MovieHistory::updateOrCreate([
                'user_id'   => $user->id,
                'movie_id'  => $movie_id,
                'series_id' => $series_index,
            ], [
                'progress'        => $progress,
                'last_watch_time' => now(),
            ]);
        }
        return true;
    }

    public static function resolveShowMovieHistory($root, $args, $content, $info)
    {
        app_track_event('长视频', '查看长视频历史记录');
        //标记获取详情数据信息模式
        request()->request->add(['fetch_sns_detail' => true]);

        //取每个电影的最新一条剧集记录
        if ($user = currentUser()) {
            $visits = MovieHistory::query()->whereNull("deleted_at")->whereIn(DB::raw('(movie_id,updated_at)'), function ($query) use ($user) {
                $query->select('movie_id', DB::raw('max(updated_at)'))
                    ->from('movie_histories')
                    ->where('user_id', $user->id)
                    ->groupBy('movie_id');
            }
            )->orderByDesc('updated_at');
            return ($visits);
        }
        return [];
    }



      // 删除用户的观影记录（隐藏）
   public static function resolveDeleteMovieViewingHistory($root, array $args, $context, $resolveInfo)
   {
       $user = getUser();
       $movie_ids = $args["movie_ids"] ?? null;
       $type = $args["type"] ?? null;


       if($type == "all"){  
        return MovieHistory::query()->where("user_id",$user->id)->delete();
       }


        return  MovieHistory::query()->where("user_id",$user->id)->whereIn("movie_id",$movie_ids)->delete();
     
   }


}
