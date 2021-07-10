<?php

namespace Haxibiao\Media\Traits;

use App\Audio;
use App\Category;

/**
 * Resolver of 音频
 */
trait AudioResolvers
{
    /**
     * 获取音频
     */
    public static function resolveAudios($root, $args, $context, $info)
    {
        if (isset($args['category_id'])) {
            $category = Category::find($args['category_id']);
            if (!is_null($category)) {
                $qb = $category->audios();
            }
        }
        $qb = isset($qb) ? $qb : Audio::query();

        return $qb;
    }
}
