<?php

namespace Haxibiao\Media\Observers;

use App\Post;
use Haxibiao\Media\Video;
use Haxibiao\Media\Jobs\MakeVideoCovers;

class VideoObserver
{
    /**
     * Handle the video "created" event.
     *
     * @param  \App\Video  $video
     * @return void
     */
    public function created(Video $video)
    {
        //启动截取图片job
        // MakeVideoCovers::dispatch($video);

    }

    /**
     * Handle the video "updated" event.
     *
     * @param  \App\Video  $video
     * @return void
     */
    public function updated(Video $video)
    {
        //也截图，改动视频，多半动视频文件，统一后，不会忘记在其他repo 方法里 截图
        //视频更新，获得了封面...

        if ($video->cover) {
            if ($post = Post::where('video_id', $video->id)->first()) {
                Post::publishPost($post);
            }
        }
    }

    /**
     * Handle the video "deleted" event.
     *
     * @param  \App\Video  $video
     * @return void
     */
    public function deleted(Video $video)
    {
        //
    }

    /**
     * Handle the video "restored" event.
     *
     * @param  \App\Video  $video
     * @return void
     */
    public function restored(Video $video)
    {
        //
    }

    /**
     * Handle the video "force deleted" event.
     *
     * @param  \App\Video  $video
     * @return void
     */
    public function forceDeleted(Video $video)
    {
        //
    }
}
