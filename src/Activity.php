<?php

namespace Haxibiao\Media;

use App\Model;
use Haxibiao\Media\Traits\ActivityAttrs;
use Haxibiao\Media\Traits\ActivityRepo;
use Haxibiao\Media\Traits\ActivityResolver;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

//FIXME: 这里是一种置顶场景，可以重构到Stickable
/**
 * Class of 活动轮播图
 */
class Activity extends Model
{
    protected $fillable = [
        'movie_id',
        'title',
        'subtitle',
        'image_url',
        'type',
        'sort',
    ];
    protected $table = 'activities';

    use ActivityRepo, ActivityAttrs, ActivityResolver;

    // 首页
    public const TYPE_INDEX = 1;
    // 电视剧
    public const TYPE_SERIE = 2;
    // 电影专题
    public const TYPE_PROJECT = 3;

    public const TYPE_SEARCH = 4;

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }
    public function scopeEnable($query)
    {
        return $query->where('status', 1);
    }
}
