<?php

namespace Haxibiao\Media;

use Illuminate\Database\Eloquent\Relations\Pivot;

class MovieRegion extends Pivot
{
    protected $guarded = [];
    protected $table   = 'movie_regions';
}
