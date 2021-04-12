<?php

namespace Haxibiao\Media\Observers;

use App\Post;
use Haxibiao\Media\Video;

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
        \info('VideoObserver created');

        $user = $video->user;

        $video->autoPublishContentWhenAboutMovie();

        //启动截取图片job
        // MakeVideoCovers::dispatch($video);

        //更新用户任务状态
        $user->reviewTasksByClass(get_class($video));

    }

    /**
     * Handle the video "updated" event.
     *
     * @param  \App\Video  $video
     * @return void
     */
    public function updated(Video $video)
    {
        //处理完封面时
        if ($video->cover) {
            if ($post = $video->post) {
                Post::publishPost($post);
            } else {
                //秀儿：试图修复采集的视频 发布后的视频动态缺少文本等信息（爱你城项目层面已修复，需要重构在breeze层面修复）
                // $spider = Spider::where('spider_type', 'videos')->where('spider_id', $video->id)->first();
                // if ($spider) {
                //     if ($post = Post::where('spider_id', $spider->id)->first()) {
                //         $post->status      = Post::PUBLISH_STATUS; //发布成功动态
                //         $post->description = $spider->data['title'] ?? '';
                //         $post->video_id    = $video->id;
                //         $post->save();
                //     }
                // }
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
