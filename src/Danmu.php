<?php

namespace Haxibiao\Media;

use Haxibiao\Breeze\Model;
use Haxibiao\Media\Traits\DanmuResolvers;

class Danmu extends Model
{

    use DanmuResolvers;

    protected $table = "danmu";
}
