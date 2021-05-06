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
        if (!blank($spider->source_url)) {
            //自动创建一个草稿动态
            Post::saveSpiderVideoPost($spider);
            //新爬虫，提交任务给哈希云,等回调
            $spider->process();
        }
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
            //media hook 回调更新成功结果过，自动发布动态
            Post::publishSpiderVideoPost($spider);
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
