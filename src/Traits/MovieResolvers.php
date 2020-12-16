<?php

namespace Haxibiao\Media\Traits;

use Haxibiao\Media\Movie;

trait MovieResolvers
{
    public function resolversCategoryMovie($root, $args, $content, $info)
    {
        return Movie::where('region', $args['category']);
    }

    public function resolversMovie($root, $args, $content, $info)
    {
        $movie = Movie::find(data_get($args,'movie_id'));
        app_track_event('看视频', '电影详情',data_get($args,'movie_id'));
        return $movie;
    }
}
