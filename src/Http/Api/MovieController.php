<?php

namespace Haxibiao\Media\Http\Api;

use App\Http\Controllers\Controller;
use App\Movie;

class MovieController extends Controller
{
    //播放器剧集
    public function getSeries(Movie $movie)
    {
        return $movie->data;
    }
}
