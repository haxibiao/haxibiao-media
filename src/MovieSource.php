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

    //兼容本地共享的medichain
    public function getTable()
    {
        if (is_enable_mediachain()) {
            return config('database.connections.mediachain.database') . ".movie_sources";
        }
        return 'movie_sources';
    }
}
