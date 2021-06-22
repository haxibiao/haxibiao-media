<?php

namespace Haxibiao\Media;

use App\Activity;
use App\Collection;
use App\Post;
use App\Series;
use Haxibiao\Breeze\Model;
use Haxibiao\Breeze\Traits\HasFactory;
use Haxibiao\Breeze\User;
use Haxibiao\Content\Traits\Stickable;
use Haxibiao\Content\Traits\WithCms;
use Haxibiao\Helpers\Traits\Searchable;
use Haxibiao\Media\Traits\MovieAttrs;
use Haxibiao\Media\Traits\MovieRepo;
use Haxibiao\Media\Traits\MovieResolvers;
use Haxibiao\Sns\Favorite;
use Haxibiao\Sns\Traits\WithSns;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    // BLGL包含内涵尺度分类剧
    public const CATEGORY_BLGL = 7;

    public const CATEGORY_JIESHUO  = 8; // 解说
    public const CATEGORY_ZHONGGUO = 9; // 中国
    public const CATEGORY_HOT      = 10; // 热门
    public const CATEGORY_NEWST    = 11; // 最新

    public const NOT_IDENTIFY = 0; // 未识别
    public const PUBLISH      = 1; // 正常（内涵云存储线路）
    public const PLAY_FIXED   = 2; // 求片已修复线路的(其他资源缓存的线路)
    public const DISABLED     = -1; // 已下架处理
    public const ERROR        = -2; // 求片中(资源损坏、丢失、不完整)

    public static function getStatuses()
    {
        return [
            Movie::NOT_IDENTIFY => "未标识",
            Movie::PUBLISH      => "正常影片",
            Movie::PLAY_FIXED   => "求片成功",
            Movie::DISABLED     => "下架处理",
            Movie::ERROR        => "求片中",
        ];
    }

    //加载data到json位series数据只给vue播放器
    protected $appends = ['data'];
    protected $casts   = [
        'data' => 'array',
    ];

    public static function boot()
    {
        parent::boot();
        //以下逻辑在本地项目做，限制住了其他项目了

        // if (!in_array(config('app.name'), ['dianyintujie', 'diudie'])) {
        //     static::addGlobalScope(new MovieStatusScope);
        // }
        // if (!in_array(config('app.name'), ['内函电影', 'yingdaquan','dianyintujie','diudie'])) {
        //     static::addGlobalScope('avaiable', function (Builder $builder) {
        //         /**
        //          * 公司实名备案的域名不展示港剧，避免版权风险
        //          * https://pm.haxifang.com/browse/NHY-380
        //          */
        //         //            注释的原因：凡是我们自己的域名先隐藏中国境内的影片，目前在诉讼期间，对方正在搜集我们的证据。
        //         //            $unRecorded = is_null(
        //         //                data_get(app('cms_site'), 'company', null)
        //         //            );
        //         //            if($unRecorded){
        //         //                return $builder;
        //         //            }
        //         $builder->whereNotIn('region', ['未知分类', '港剧'])
        //             ->where(function ($query) {
        //                 $query->whereNotIn('country', ['中国大陆', '中国', '香港', '大陆'])->where('region', '解说')
        //                     ->orWhereNotNull('region');
        //             })
        //         ;
        //     });
        // }
    }

    protected $searchable = [
        'columns' => [
            'movies.name'     => 3,
            // 'movies.introduction' => 2, //暂时不搜索简介了，太慢
            'movies.producer' => 2,
            'movies.actors'   => 1,
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

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function scopeEnable($query)
    {
        return $query->whereIn('status', [self::PUBLISH])->whereNotNull('cover');
    }

    public function scopePublish($query)
    {
        return $query->where('status', Movie::PUBLISH);
    }

    public function scopeHanju($query)
    {
        return $query->where('country', '韩国');
    }

    public function post()
    {
        return $this->hasOne(Post::class);
    }

    public function collection()
    {
        return $this->hasOne(Collection::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function videos()
    {
        return $this->hasMany(Video::class);
    }

    public function favorites()
    {
        return $this->morphMany(Favorite::class, 'favorable');
    }

    public function getPostAttribute()
    {
        return $this->posts()->first() ?? null;
    }
}
