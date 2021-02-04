<?php

namespace Haxibiao\Media;

use App\Activity;
use App\Series;
use Haxibiao\Breeze\Model;
use Haxibiao\Breeze\Traits\HasFactory;
use Haxibiao\Helpers\Traits\Searchable;
use Haxibiao\Media\Scopes\MovieStatusScope;
use Haxibiao\Media\Traits\CanLinkMovie;
use Haxibiao\Media\Traits\MovieAttrs;
use Haxibiao\Media\Traits\MovieRepo;
use Haxibiao\Media\Traits\MovieResolvers;
use Haxibiao\Sns\Traits\WithSns;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Movie extends Model
{
    use HasFactory;
    use MovieRepo;
    use Searchable;
    use MovieResolvers;
    use MovieAttrs;
    use WithSns;
    use CanLinkMovie;

    protected $guarded = [];

    protected $table = 'movies';

    const CATEGORY_JIESHUO = 0;
    const MOVIE_RI_JU      = 1;
    const MOVIE_MEI_JU     = 2;
    const MOVIE_HAN_JU     = 3;
    const MOVIE_GANG_JU    = 4;

    public const NOT_IDENTIFY = 0; //未识别
    public const PUBLISH      = 1; //正常影片
    public const NEIHAN       = 2; //“内涵”影片（尺度较大）
    public const DISABLED     = -1; //下架处理
    public const ERROR        = -2; //资源损坏、丢失、不完整

    //加载data到json位series数据只给vue播放器
    public $appends = ['data'];
    public $casts   = [
        'data' => 'array',
    ];

    public static function boot()
    {
        parent::boot();
        static::addGlobalScope(new MovieStatusScope);
    }

    protected $searchable = [
        'columns' => [
            'movies.name'         => 3,
            'movies.introduction' => 2,
            'movies.actors'       => 1,
        ],
    ];

    public function getMorphClass()
    {
        return 'movies';
    }

    public function activity(): HasOne
    {
        return $this->hasOne(Activity::class);
    }

    public function series(): HasMany
    {
        return $this->hasMany(Series::class);
    }

    public function scopeEnable($query)
    {
        return $query->whereIn('status', [self::PUBLISH])->whereNotNull('cover');
    }

    public function scopePublish($query)
    {
        return $query->where('status', Movie::PUBLISH);
    }

    public static function getStatuses()
    {
        return [
            Movie::NOT_IDENTIFY => "未标识",
            Movie::PUBLISH      => "正常影片",
            Movie::NEIHAN       => "尺度较大",
            Movie::DISABLED     => "下架处理",
            Movie::ERROR        => "资源损坏、失效、残缺",
        ];
    }
}
