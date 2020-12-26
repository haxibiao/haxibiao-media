<?php

namespace Haxibiao\Media;

use App\Comment;
use App\Favorite;
use App\Series;
use Haxibiao\Helpers\Traits\Searchable;
use Haxibiao\Media\Traits\MovieAttrs;
use Haxibiao\Media\Traits\MovieRepo;
use Haxibiao\Media\Traits\MovieResolvers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Movie extends Model
{
    use MovieRepo;
    use Searchable;
    use MovieResolvers;
    use MovieAttrs;

    protected $guarded = [];

    const MOVIE_RI_JU   = 1;
    const MOVIE_MEI_JU  = 2;
    const MOVIE_HAN_JU  = 3;
    const MOVIE_GANG_JU = 4;

    public $casts = [
        'data' => 'array',
    ];

    protected $searchable = [
        'columns' => [
            'movies.name'         => 3,
            'movies.introduction' => 2,
            'movies.actors'       => 1,
        ],
    ];

    public function series(): HasMany
    {
        return $this->hasMany(Series::class);
    }

    public function getCoverUrlAttribute()
    {
        return $this->cover;
    }

    public function favorites()
    {
        return $this->morphMany(Favorite::class, 'faved');
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

}
