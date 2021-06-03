<?php

namespace Haxibiao\Media\Traits;

use Haxibiao\Media\Jobs\CrawlCollection;
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
        $user = getUser();

        //暂时不控制粘贴视频需要精力点的检查，现在粘贴的太少了

        $content  = data_get($args, 'description') ?? data_get($args, 'content');
        $tagNames = data_get($args, 'tag_names', []);
        $spider   = Spider::resolveDouyinVideo($user, $args['share_link'], $content, $tagNames);
        return $spider;
    }

    public function fastResolverDouyinVideo($root, $args, $context, $info)
    {
        $user    = getUser();
        $content = data_get($args, 'content');
        return SpiderRepo::pasteDouyinVideo($user, $args['share_link'], $content);
    }

    public function crawlCollection($root, $args, $context, $info)
    {
        $user            = getUser();
        $user_share_link = $args['user_share_link'];
        dispatch(new CrawlCollection($user, $user_share_link));
        return true;
    }
}
