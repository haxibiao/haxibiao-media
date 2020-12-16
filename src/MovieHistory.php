<?php
namespace Haxibiao\Media;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class MovieHistory extends Model
{
    protected $guarded = [];
    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }
}

