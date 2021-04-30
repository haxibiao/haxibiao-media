<?php

namespace Haxibiao\Media\Observers;

use App\Post;
use Haxibiao\Media\Spider;

class SpiderObserver
{
    /**
     * Handle the spider "created" event.
     *
     * @param  \App\Spider  $spider
     * @return void
     */
    public function created($spider)
    {
        //创建爬虫的时候，自动发布一个动态
        Post::saveSpiderVideoPost($spider);
    }

    /**
     * Handle the spider "updated" event.
     *
     * @param  \App\Spider  $spider
     * @return void
     */
    public function updated($spider)
    {
        if ($spider->status == Spider::PROCESSED_STATUS) {
            if ($user = $spider->user) {
                //更新任务状态
                $user->reviewTasksByClass(get_class($spider));
            }
        }
    }

    /**
     * Handle the spider "deleted" event.
     *
     * @param  \App\Spider  $spider
     * @return void
     */
    public function deleted($spider)
    {
        //
    }

    /**
     * Handle the spider "restored" event.
     *
     * @param  \App\Spider  $spider
     * @return void
     */
    public function restored(Spider $spider)
    {
        //
    }

    /**
     * Handle the spider "force deleted" event.
     *
     * @param  \App\Spider  $spider
     * @return void
     */
    public function forceDeleted(Spider $spider)
    {
        //
    }
}
