<?php

namespace Haxibiao\Media\Traits;

use App\Post;
use Haxibiao\Media\Spider;
use Illuminate\Support\Arr;

trait SpiderResolvers
{
    public function resolveSpiders($root, $args, $context, $info)
    {
        $type = Arr::get($args, 'type', null);
        return Spider::querySpiders(getUser(), $type);
    }

    public function resolveShareLink($root, $args, $context, $info)
    {

        $spider = static::resolveDouyinVideo(getUser(false), $args['share_link']);
        $post   = Post::with('video')->firstOrNew(['spider_id' => $spider->id]);

        $content = data_get($args, 'content');
        if ($content) {
            $post->content = $content;
        }

        $description = data_get($args, 'description');
        if ($description) {
            $post->description = $description;
        }
        // 标签
        $tagNames = data_get($args, 'tag_names', []);
        $post->tagByNames($tagNames);
        $post->save();

        return $spider;
    }
}
