<?php
namespace Haxibiao\Media;

use App\Movie;
use App\Series;
use Haxibiao\Breeze\Model;
use Haxibiao\Breeze\Traits\HasFactory;
use Haxibiao\Media\Traits\MovieHistoryAttrs;
use Haxibiao\Media\Traits\MovieHistoryResolvers;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovieHistory extends Model
{
    use HasFactory;
    use MovieHistoryAttrs;
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
