<?php

namespace Haxibiao\Media\Observers;

use Haxibiao\Breeze\Notifications\BreezeNotification;
use Haxibiao\Media\Movie;
use Haxibiao\Media\MovieUser;

class MovieObserver
{
    public function created(Movie $movie)
    {

    }

    public function saving (Movie $movie)
    {
        $playLines = $movie->play_lines ?? [];
		$movie->has_playurl = (count($playLines) == 0) ? 0 : 1;// 0代表有播放线路
    }

    public function updating(Movie $movie)
    {
        if ($movie->isDirty('data')) {
            //统计默认线路的剧集数
            $movie->count_series = count($movie->series);
            //剧集更新通知
            $users  = $movie->findUsers;
            $sender = currentUser();
            foreach ($users as $user) {
                $user->notify(new BreezeNotification($sender, $movie->id, 'movies', '已更新' . $movie->count_series . '集', $movie->cover, $movie->name, '更新了剧集'));
                $user->pivot->update(['report_status' => MovieUser::UPDATED]);
            }
        }
    }

    public function updated(Movie $movie)
    {
    	// 清理GQL缓存
		cache()->delete(sprintf('query:movie:movie_id:%d',data_get($movie,'id')));
    }

    public function deleted(Movie $movie)
    {
		// 清理GQL缓存
		cache()->delete(sprintf('query:movie:movie_id:%d',data_get($movie,'id')));
    }

    public function restored(Movie $movie)
    {

    }

    public function forceDeleted(Movie $movie)
    {

    }
}
