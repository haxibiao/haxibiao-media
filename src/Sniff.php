<?php

namespace Haxibiao\Media;

use Haxibiao\Breeze\Model;

class Sniff extends Model
{

    protected $table = "sniff";

    public const PUBLISH   = 1;

    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }

}
