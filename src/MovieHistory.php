<?php
namespace Haxibiao\Media;

use Haxibiao\Media\Traits\MovieHistoryResolvers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovieHistory extends Model
{
    use MovieHistoryResolvers;

    protected $guarded = [];

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }
}
