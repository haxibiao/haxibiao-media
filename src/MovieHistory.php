<?php
namespace Haxibiao\Media;

use App\Movie;
use App\Series;
use Haxibiao\Base\Model;
use Haxibiao\Media\Traits\MovieHistoryResolvers;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovieHistory extends Model
{
    use MovieHistoryResolvers;

    protected $guarded = [];

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class);
    }
}
