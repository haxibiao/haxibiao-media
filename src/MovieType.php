<?php

namespace Haxibiao\Media;

use Illuminate\Database\Eloquent\Relations\Pivot;

class MovieType extends Pivot
{
    protected $guarded = [];
    protected $table   = 'movie_types';
}
