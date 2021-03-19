<?php

namespace Haxibiao\Media;

use App\Post;
use App\Question;
use App\User;
use App\Video;
use Haxibiao\Breeze\Model;
use Haxibiao\Breeze\Traits\HasFactory;
use Haxibiao\Content\Traits\Taggable;
use Haxibiao\Media\Traits\SpiderAttrs;
use Haxibiao\Media\Traits\SpiderRepo;
use Haxibiao\Media\Traits\SpiderResolvers;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Spider extends Model
{
    use HasFactory;

    use SpiderAttrs;
    use SpiderRepo;
    use SpiderResolvers;
    use Taggable;

    protected $fillable = [
        'user_id',
        'source_url',
        'raw',
        'data',
        'status',
        'spider_type',
        'count',
        'spider_id',
        'retries',
    ];

    protected $casts = [
        'raw'  => 'array',
        'data' => 'array',
    ];

    const WATING_STATUS    = 0;
    const PROCESSED_STATUS = 1;
    const FAILED_STATUS    = -1;
    const INVALID_STATUS   = -2;

    const SPIDER_GOLD_REWARD = 10;

    const VIDEO_TYPE    = 'videos';
    const QUESTION_TYPE = 'questions';

    const COLLECTIONS_URL = "https://aweme-lq.snssdk.com/aweme/v1/mix/list/?user_id=%s&cursor=%s&count=%s";
    const VIDEOS_URL      = "https://aweme-lq.snssdk.com/aweme/v1/mix/aweme/?mix_id=%s&cursor=%s&count=%s";

    const DOUYIN_VIDEO_DOMAINS = [
        'v.douyin.com',
        'www.iesdouyin.com',
        'vm.tiktok.com',
        'vt.tiktok.com',
        'v.kuaishou.com',
    ];

    public static function boot()
    {
        parent::boot();

        self::saving(function ($spider) {
            $spider->replaceTitleBadWord();
        });

        self::created(function ($spider) {
            //创建爬虫的时候，自动发布一个动态
            Post::saveSpiderVideoPost($spider);
        });

        self::updated(function ($spider) {
            if ($spider->status == Spider::PROCESSED_STATUS) {
                Post::publishSpiderVideoPost($spider);
                $post = Post::where(['spider_id' => $spider->id])->first();
                if ($post) {
                    $user = $post->user;
                    //更新任务状态
                    $user->reviewTasksByClass(get_class($spider));
                }
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function video()
    {
        return $this->belongsTo(Video::class, 'spider_id');
    }

    public function scopeWating($query)
    {
        return $query->where('status', self::WATING_STATUS);
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', self::PROCESSED_STATUS);
    }

    public function scopeProcessFailed($query)
    {
        return $query->where('status', '<', self::WATING_STATUS);
    }

    public function spider(): MorphTo
    {
        return $this->morphTo('spider');
    }

    public static function getStatuses()
    {
        return [
            self::WATING_STATUS    => '待处理',
            self::PROCESSED_STATUS => '已处理',
            self::FAILED_STATUS    => '失败的',
            self::INVALID_STATUS   => '无效的',
        ];
    }

    public static function getTypeEnums()
    {
        return [
            self::VIDEO_TYPE    => [
                'value'       => self::VIDEO_TYPE,
                'description' => '视频',
            ],
            self::QUESTION_TYPE => [
                'value'       => self::QUESTION_TYPE,
                'description' => '题目',
            ],
        ];
    }

}
