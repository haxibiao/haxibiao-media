<?php

namespace Haxibiao\Media;

use Illuminate\Database\Eloquent\Relations\Pivot;

class MovieDirector extends Pivot
{
    protected $guarded = [];
    protected $table   = 'movie_directors';
}
