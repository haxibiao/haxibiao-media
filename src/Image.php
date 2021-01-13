<?php

namespace Haxibiao\Media;

use Haxibiao\Breeze\Model;
use Haxibiao\Media\Traits\ImageAttrs;
use Haxibiao\Media\Traits\ImageRepo;
use Haxibiao\Media\Traits\ImageResolvers;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Image extends Model
{
    use ImageAttrs;
    use ImageRepo;
    use ImageResolvers;

    protected $guarded = [];

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
