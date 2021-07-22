<?php

namespace Haxibiao\Media;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Director extends Model
{
    protected $guarded = [];
    public function movies(): BelongsToMany
    {
        return $this->belongsToMany(Movie::class, 'movie_directors')
            ->using(MovieDirector::class);
    }
}
