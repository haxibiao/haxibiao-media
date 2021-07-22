<?php

namespace Haxibiao\Media;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Type extends Model
{
    protected $guarded = [];

    public function movies(): BelongsToMany
    {
        return $this->belongsToMany(Movie::class, 'movie_types')
            ->using(MovieType::class);
    }
}
