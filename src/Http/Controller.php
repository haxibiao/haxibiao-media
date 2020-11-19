<?php

namespace Haxibiao\Media\Http;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    // FIXME: PSR-4 配合 composer autoload 自动加载, 类的位置需要变更 
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
