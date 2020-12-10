<?php

namespace Haxibiao\Content\Console;

use Haxibiao\Media\Movie;
use Illuminate\Console\Command;

class MoviePush extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'movie:push';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '将本站的电影push到mediachain';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Movie::query()->where('status', 1)->chunk(1000, function ($movies) {
            foreach ($movies as $movie) {
                $series     = $movie->series()->get(['path', 'name', 'bucket']);
                $movie      = $movie->toResource();
                $seriesJson = [];
                foreach ($series as $item) {
                    $seriesJson[] = [
                        'name' => $item->name,
                        'url'  => Movie::getCDNDomain($item->bucket) . $item->path,
                    ];
                }
                $exists = \DB::connection('mediachain')->table('movies')->where([
                    'name'       => $movie['name'],
                    'source'     => app('app.name'),
                    'source_key' => $movie['id'],
                ])->exists();

                if (!$exists) {
                    \DB::connection('mediachain')->table('movies')->insert([
                        'name'         => $movie['name'],
                        'introduction' => $movie['introduction'],
                        'year'         => $movie['year'],
                        'count_series' => $movie['count_series'],
                        'producer'     => $movie['producer'],
                        'region'       => $movie['region'],
                        'cover'        => $movie['cover'],
                        'rank'         => $movie['rank'],
                        'country'      => $movie['country'],
                        'subname'      => $movie['subname'],
                        'score'        => $movie['score'],
                        'tags'         => $movie['tags'],
                        'hits'         => $movie['hits'],
                        'lang'         => $movie['lang'],
                        'type_name'    => $movie['type_name'],
                        'source'       => config('app.name'),
                        'source_key'   => $movie['id'],
                        'data'         => json_encode($seriesJson),
                    ]);
                    $this->info("同步{$movie['name']}数据到 media chain 成功");
                }
            }
        });
    }
}
