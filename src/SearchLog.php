<?php

namespace Haxibiao\Media;

use Haxibiao\Breeze\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchLog extends Model
{

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\User::class);
    }

}
