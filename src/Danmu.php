<?php

namespace Haxibiao\Media;

use GuzzleHttp\Client;
use Haxibiao\Breeze\Model;
use Haxibiao\Media\Traits\DanmuResolvers;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Danmu extends Model
{

    use DanmuResolvers;

    protected $table = "danmu";

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }

    public static function syncToMedia(Danmu $danmu)
    {
        $client = new Client(['base_uri' => 'http://media.haxibiao.com/']);
        $client->request('POST', 'api/danmu/store', [
            'http_errors' => false,
            'form_params' => [
                'body'            => $danmu->content,
                'source'          => config('app.name'),
                'source_id'       => $danmu->id,
                'series_name'     => $danmu->series_name,
                'source_movie_id' => $danmu->movie_id,
                'movie_name'      => $danmu->movie->name,
            ],
        ]);
    }
}
