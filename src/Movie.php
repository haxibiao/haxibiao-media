<?php

namespace Haxibiao\Media;

use App\Activity;
use App\Post;
use App\Series;
use Haxibiao\Breeze\Model;
use Haxibiao\Breeze\Traits\HasFactory;
use Haxibiao\Content\Traits\Stickable;
use Haxibiao\Content\Traits\WithCms;
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

    use WithCms;
    use Stickable;

    protected $guarded = [];

    protected $table = 'movies';

    /**
     * 日韩美港剧
     */
    public const CATEGORY_RI   = 1;
    public const CATEGORY_MEI  = 2;
    public const CATEGORY_HAN  = 3;
    public const CATEGORY_GANG = 4;
    public const CATEGORY_TAI  = 5;
    public const CATEGORY_YIN  = 6;
    // boy love,girl love
    public const CATEGORY_BLGL     = 7;
    public const CATEGORY_JIESHUO  = 8; // 解说
    public const CATEGORY_ZHONGGUO = 9; // 中国
    public const CATEGORY_HOT      = 10; // 热门
    public const CATEGORY_NEWST    = 11; // 最新

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

    public function scopeHanju($query)
    {
        return $query->where('country', '韩国');
    }

    public function post()
    {
        return $this->hasOne(Post::class);
    }
}
