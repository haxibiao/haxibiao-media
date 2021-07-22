<?php

namespace Haxibiao\Media;

use Illuminate\Database\Eloquent\Relations\Pivot;

class MovieActor extends Pivot
{
    protected $guarded = [];
    protected $table   = 'movie_actors';
}
