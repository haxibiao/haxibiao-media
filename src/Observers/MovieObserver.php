<?php

namespace Haxibiao\Media\Observers;

use Haxibiao\Breeze\Notifications\BreezeNotification;
use Haxibiao\Breeze\User;
use Haxibiao\Media\Movie;

class MovieObserver
{
    /**
     * Handle the Movie "created" event.
     *
     * @param  \App\Movie  $movie
     * @return void
     */
    public function created(Movie $movie)
    {
        //
    }

    public function updating(Movie $movie)
    {
        if ($movie->isDirty('data')) {
            //统计默认线路的剧集数
            $movie->count_series = count($movie->series);
            //剧集更新通知
            if ($user = User::find($movie->user_id)) {
                $user->notify(new BreezeNotification(currentUser(), $movie->id, 'movies', '已更新' . $movie->count_series . '集', $movie->cover, $movie->name, '更新了剧集'));
            }
        }
    }

    public function updated(Movie $movie)
    {

    }

    /**
     * Handle the Movie "deleted" event.
     *
     * @param  \App\Movie  $movie
     * @return void
     */
    public function deleted(Movie $movie)
    {
        //
    }

    /**
     * Handle the Movie "restored" event.
     *
     * @param  \App\Movie  $movie
     * @return void
     */
    public function restored(Movie $movie)
    {
        //
    }

    /**
     * Handle the Movie "force deleted" event.
     *
     * @param  \App\Movie  $movie
     * @return void
     */
    public function forceDeleted(Movie $movie)
    {
        //
    }
}
