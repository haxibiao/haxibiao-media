<?php

namespace haxibiao\media;

use App\Model;
use haxibiao\media\Traits\ImageAttrs;
use haxibiao\media\Traits\ImageRepo;
use haxibiao\media\Traits\ImageResolvers;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Image extends Model
{
    use ImageAttrs;
    use ImageRepo;
    use ImageResolvers;

    protected $fillable = [
        'path',
        'path_origin',
        'path_small',

        //答题
        'user_id',
        'hash',
        // 'path',
        'width',
        'height',
        'extension',
        'count',
    ];

    public function articles()
    {
        return $this->belongsToMany('App\Article');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\User::class);
    }

    //FIXME: 答题里的代码 很奇怪这个关系干嘛用
    public function images()
    {
        return $this->belongsToMany(Image::class, 'imageable')
            ->withPivot('created_at');
    }

}
