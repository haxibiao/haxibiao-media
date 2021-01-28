<?php

namespace Haxibiao\Media;

use Haxibiao\Breeze\Traits\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinkMovie extends Model
{
    use HasFactory;
    protected $table = 'link_movie';
    public $fillable = [
        'movie_id',
        'linked_id',
        'linked_type',
    ];

    public function linked()
    {
        return $this->morphTo();
    }

    public function movie()
    {
        return $this->belongsTo(\App\Movie::class);
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(\App\Collection::class, 'linked_id');
    }
    public function post(): BelongsTo
    {
        return $this->belongsTo(\App\Post::class, 'linked_id');
    }
    //电影解说
    public function commentary(): BelongsTo
    {
        return $this->belongsTo(\App\Movie::class, 'linked_id');
    }
}
