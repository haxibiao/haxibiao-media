<?php

namespace Haxibiao\Media;

use Haxibiao\Breeze\Model;
use Haxibiao\Breeze\Traits\HasFactory;
use Haxibiao\Media\Traits\SeriesAttrs;
use Haxibiao\Media\Traits\SeriesRepo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Series extends Model
{
    use HasFactory;

    use SeriesRepo;
    use SeriesAttrs;

    public const STATUS_DEFAULT = 0;
    public const STATUS_ENABLE  = 1;
    public const STATUS_DISABLE = -1;

    protected $appends = ['url'];

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }
}
