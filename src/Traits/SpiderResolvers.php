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
        //FIXME: 修复到自己的app层的 resolve methods 覆盖项目逻辑
        if (!in_array(config('app.name'), ['yinxiangshipin', 'ainicheng', 'dongwaimao', 'ablm', 'nashipin', 'caohan', 'dongwaimao'])) {
            throw_if($user->ticket < 1, UserException::class, '分享失败,精力点不足,请补充精力点!');
        }

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
