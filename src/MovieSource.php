<?php

namespace Haxibiao\Media;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovieSource extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'play_urls' => 'array',
    ];

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }

    //兼容本地movies，和共享的medichain模式
    public function getTable()
    {
        if (config('media.enable_mediachain')) {
            return "mediachain.movie_sources";
        }
        return 'movies';
    }
}
