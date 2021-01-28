<?php

namespace Haxibiao\Media;

use Haxibiao\Breeze\Model;
use Haxibiao\Breeze\Traits\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\User::class);
    }

}
