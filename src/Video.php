<?php

namespace haxibiao\media;

use App\Model;
use haxibiao\media\Traits\MakeCovers;
use haxibiao\media\Traits\VideoAttrs;
use haxibiao\media\Traits\VideoRepo;
use haxibiao\media\Traits\VideoResolvers;
use Illuminate\Database\Eloquent\SoftDeletes;

class Video extends Model
{
    use SoftDeletes;
    use VideoResolvers;
    use VideoAttrs;
    use VideoRepo;
    use MakeCovers;

    protected $casts = [
        'json' => 'object',
    ];

    protected $fillable = [
        'title',
        'user_id',
        'path',
        'duration',
        'json',
        'cover',
        'hash',
        'disk',
        'fileid', //FIXME: 答题记得qcvod_fileid 改名

        //答题补充的
        'filename',
        'created_at',
        'deleted_at',
        'updated_at',

        'app',
        'type',
        'status',

        'width',
        'height',

    ];

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

    public function user()
    {
        return $this->belongsTo(\App\User::class);
    }

    public function article()
    {
        return $this->hasOne(\App\Article::class);
    }

}
