<?php

namespace Haxibiao\Media\Traits;

use Haxibiao\Media\Image;
use Haxibiao\Media\Spider;
use Haxibiao\Media\Video;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait WithImage
{
    public function images()
    {
        if (in_array(config('app.name'), [
            'yinxiangshipin', 'dongmiaomu', 'caohan', 'dongmeiwei',
            'dongdianyi', 'quanminwenti', 'dongdaima', 'ainicheng',
            'dongshouji', 'dongyundong', 'dongwaiyu', 'dongwaimao',
            'buyueta', 'dongdianhai', 'jinlinle', 'youjianqi', 'nashipin',
            'yanjiao', 'hengyang', 'dongwanche', 'jucheshe', 'ruqunba', 'haxibiao'
        ])) {
            return $this->morphToMany(Image::class, 'imageable', 'imageable')
                ->withTimestamps();
        }
        return $this->hasMany(Image::class);
    }
}
