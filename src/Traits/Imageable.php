<?php

namespace Haxibiao\Media\Traits;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

class Imageable extends Pivot
{
    protected $fillable = [
        'imageable_id',
        'imageable_type',
        'image_id',
    ];

    const UPDATED_AT = null;

    /**
     * 图片对象
     */
    public function image()
    {
        return $this->belongsTo(Image::class);
    }

    /**
     * 图片被这些对象关联的（imageable = imageable items query builder）
     */
    public function imageable(): MorphToMany
    {
        return $this->morphToMany(Imageable::class, 'imageable');
    }

}
