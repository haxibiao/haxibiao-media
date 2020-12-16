<?php

namespace Haxibiao\Media\Traits;

use Haxibiao\Media\Movie;

trait MovieResolvers
{
    public function resolversCategoryMovie($root, $args, $content, $info)
    {
        return Movie::where('region', $args['category']);
    }
}
