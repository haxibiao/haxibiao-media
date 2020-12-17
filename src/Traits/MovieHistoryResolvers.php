<?php

namespace Haxibiao\Media\Traits;

use App\MovieHistory as AppMovieHistory;
use Haxibiao\Media\MovieHistory;
use Illuminate\Support\Facades\DB;

trait MovieHistoryResolvers
{
    //resolvers
    public static function saveWatchProgress($root, $args, $content, $info)
    {
        $movie_id = data_get($args, 'movie_id');
        $series_index = data_get($args, 'series_index');
        $progress = data_get($args, 'progress');
        if (checkUser()) {
            $user = getUser();
            // 保存观看历史,存储每集的进度
            //$series_id,非series表主键，存储的值为series数组索引index
            MovieHistory::updateOrCreate([
                'user_id' => $user->id,
                'movie_id' => $movie_id,
                'series_id' => $series_index,
            ], [
                'progress' => $progress,
                'last_watch_time' => now(),
            ]);
        }
        return true;
    }

    public static function showMovieHistoryResolver($root, $args, $content, $info)
    {
        //todo:
        //取每个电影的最新一条剧集记录 
        DB::enableQueryLog();
        if (checkUser()) {
            $user = getUser();
            return MovieHistory::whereIn(DB::raw('(movie_id,updated_at)'),function ($query)use($user) {
                    $query->select('movie_id',DB::raw('max(updated_at)'))
                        ->from('movie_histories')
                        ->where('user_id', $user->id)
                        ->groupBy('movie_id');
                }
            );
        }
        return [];
    }
    public static function getSeriesHistoryResolver($root, $args, $content, $info)
    {
        $movieHistory =$root;
        $movie   = $root->movie;
        $series=$movie->data;
        $seriesHistories=MovieHistory::where('user_id',$movieHistory->user_id)
        ->where('movie_id',$movieHistory->movie_id)->get();
        foreach($seriesHistories as $seriesHistory){
            $series[$seriesHistory->series_id]['progress']=$seriesHistory->progress;
        }
        $returnMovie=[];
        $returnMovie['id']=$movie->id;
        $returnMovie['name']=$movie->name;;
        $returnMovie['series_history']=$series;

        return $returnMovie;
    }
}
