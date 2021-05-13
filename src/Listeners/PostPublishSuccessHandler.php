<?php

namespace Haxibiao\Media\Listeners;

use Haxibiao\Content\Post;
use Haxibiao\Media\Events\PostPublishSuccess;
use Haxibiao\Wallet\Gold;

class PostPublishSuccessHandler
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(PostPublishSuccess $event)
    {
        $post = $event->post;
        $user = data_get($post, 'user');
        if (!is_null($user)) {
            //触发奖励
            if ($user->id == 2) {
                Gold::makeIncome($user, Post::SHARE_DOIYIN_VIDEO_REWARD, 'test_分享视频奖励');
            } else {
                Gold::makeIncome($user, Post::SHARE_DOIYIN_VIDEO_REWARD, '分享视频奖励');
            }
            //扣除精力-1
            if ($user->ticket > 0) {
                $user->decrement('ticket');
            }
        }
    }
}
