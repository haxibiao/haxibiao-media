<?php

namespace Haxibiao\Media;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Imageable extends Pivot
{
    protected $table='imageable';

    protected $fillable = [
        'imageable_id',
        'imageable_type',
        'image_id',
    ];

    const UPDATED_AT = null;

    public function image()
    {
        return $this->belongsTo(Image::class);
    }

}
