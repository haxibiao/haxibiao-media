<?php

namespace haxibiao\media;

use App\Model;
use App\Question;
use App\User;
use App\Video;
use haxibiao\media\Traits\SpiderRepo;
use haxibiao\media\Traits\SpiderResolvers;

class Spider extends Model
{
    use SpiderRepo;
    use SpiderResolvers;

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

    const DOUYIN_VIDEO_DOMAIN = 'v.douyin.com';

    public static function boot()
    {
        parent::boot();

        self::saving(function ($spider) {
            $spider->replaceTitleBadWord();
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
