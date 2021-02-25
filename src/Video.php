<?php

namespace Haxibiao\Media;

use App\Article;
use App\Post;
use App\User;
use Haxibiao\Breeze\Model;
use Haxibiao\Content\Traits\WithCms;
use Haxibiao\Media\Traits\MakeCovers;
use Haxibiao\Media\Traits\VideoAttrs;
use Haxibiao\Media\Traits\VideoRepo;
use Haxibiao\Media\Traits\VideoResolvers;
use Haxibiao\Breeze\Traits\HasFactory;
use Haxibiao\Sns\Traits\Shareable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Video extends Model
{
    use HasFactory;

    use SoftDeletes;
    use VideoResolvers;
    use VideoAttrs;
    use VideoRepo;
    use MakeCovers;
    use Shareable;

    use WithCms;

    protected $casts = [
        'json' => 'object',
    ];

    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        self::updated(function ($video) {
            if ($video->cover) {
                if ($post = Post::where('video_id', $video->id)->first()) {
                    Post::publishPost($post);
                }
            }
        });
    }
    /**
     * 状态:
     * -1:视频已损坏
     * 0:未处理
     * 1:已上传到CDN
     * 2.已截图
     * 3.已转码
     */
    public const FAILED_STATUS      = -1;
    public const UNPROCESS_STATUS   = 0;
    public const CDN_VIDEO_STATUS   = 1;
    public const COVER_VIDEO_STATUS = 2;
    public const TRANSCODE_STATUS   = 3;

    protected $appends = ['url'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    //FIXME: 这里兼容哈希表video对象过复杂的情况 重构好后，也应该去掉
    public function article(): HasOne
    {
        return $this->hasOne(Article::class);
    }

    public function post(): HasOne
    {
        return $this->hasOne(Post::class);
    }

    public function setJsonData($key, $value)
    {
        $data       = (array) $this->json;
        $data[$key] = $value;
        $this->json = $data;

        return $this;
    }
}
