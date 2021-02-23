<?php

namespace Haxibiao\Media;

use Haxibiao\Breeze\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MovieShowType extends Model
{
    use HasFactory;
    protected $table = "movie_show_types";

    protected $casts = [
        'movie_ids' => 'array',
    ];

    public function getMoviesAttribute()
    {
        return Movie::whereIn('id', $this->movie_ids)->get();
    }

    public function movieShowTypeList()
    {
        return MovieShowType::query();
    }
}
