<?php

namespace Haxibiao\Media\Traits;

use Haxibiao\Media\Traits\ActivityRepo;

/**
 * Resolver of 活动轮播图
 */
trait ActivityResolver
{
    /**
     * 获取活动轮播图
     */
    public static function getActivities($root, $args, $context, $info) 
    {
        // 活动轮播图类型
        $type = $args['type'];

        return ActivityRepo::activities($type);
    }
}