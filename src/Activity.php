<?php

namespace Haxibiao\Media;

use Haxibiao\Base\Model;
use Haxibiao\Media\Traits\ActivityRepo;
use Haxibiao\Media\Traits\ActivityAttrs;
use Haxibiao\Media\Traits\ActivityResolver;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class of 活动轮播图
 */
class Activity extends Model
{
    protected $table = 'activities';

    use ActivityRepo, ActivityAttrs, ActivityResolver;
    
    // 首页
    public const TYPE_INDEX = 1;
    // 电视剧
    public const TYPE_SERIE = 2;
    // 电影专题
    public const TYPE_PROJECT = 3;


    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie :: class);
    }

}
