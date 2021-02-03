<?php

namespace Haxibiao\Media\Traits;

use Haxibiao\Media\Image;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait WithImage
{
    //FIXME: 多余带图的内容都有封面cover属性，这里可以集中处理 getCoverAttribute

    public function images()
    {
        if (!in_array(config('app.name'), [
            'datizhuanqian',
        ])) {
            return $this->morphToMany(Image::class, 'imageable')
                ->withTimestamps();

        }
        return $this->hasMany(Image::class);
    }

    public function hasImages()
    {
        return $this->hasMany(Image::class);
    }
}
