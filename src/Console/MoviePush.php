<?php

namespace Haxibiao\Media\Console;

use App\Movie;
use Haxibiao\Media\Traits\MovieRepo;
use Illuminate\Console\Command;

class MoviePush extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature   = 'movie:push';
    public const CACHE_KEY = "movie_sync_max_id";
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
        // 从小到大push数据
        $qb    = Movie::query()->oldest('id')->where('status', 1);
        $maxid = \Cache::get(self::CACHE_KEY);
        // 跳过已push的数据，从上次结束的地方开始push
        if ($maxid) {
            $qb->where('id', '>', $maxid);
        }
        $qb->chunk(1000, function ($movies) {
            foreach ($movies as $movie) {
                $series        = $movie->series()->get(['path', 'name', 'bucket', 'source']);
                $movieResource = $movie->toResource();
                $seriesJson    = [];
                $sourceJson    = [];
                foreach ($series as $item) {
                    $seriesJson[] = [
                        'name' => $item->name,
                        'url'  => MovieRepo::getCDNDomain($item->bucket) . $item->path,
                    ];
                    $sourceJson[] = [
                        'name' => $item->name,
                        'url'  => $item->source,
                    ];
                }
                $exists = \DB::connection('mediachain')->table('movies')->where([
                    'name'       => $movieResource['name'],
                    'source'     => config('app.name'),
                    'source_key' => $movieResource['id'],
                ])->exists();

                if (!$exists) {
                    \DB::connection('mediachain')->table('movies')->insert([
                        'name'         => $movieResource['name'],
                        'actors'       => $movieResource['actors'],
                        'introduction' => $movieResource['introduction'],
                        'year'         => $movieResource['year'],
                        'count_series' => $movieResource['count_series'],
                        'producer'     => $movieResource['producer'],
                        'region'       => $movieResource['region'],
                        'cover'        => $movieResource['cover'],
                        'is_neihan'    => $movieResource['is_neihan'],
                        'rank'         => $movieResource['rank'],
                        'country'      => $movieResource['country'],
                        'subname'      => $movieResource['subname'],
                        'score'        => $movieResource['score'],
                        'tags'         => $movieResource['tags'],
                        'hits'         => $movieResource['hits'],
                        'lang'         => $movieResource['lang'],
                        'type_name'    => $movieResource['type_name'],
                        'source'       => config('app.name'),
                        'source_key'   => $movieResource['id'],
                        'data'         => json_encode($seriesJson),
                        'data_source'  => json_encode($sourceJson),
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);
                    $this->info("同步{$movie['name']}数据到 media chain 成功");
                    \Cache::put(self::CACHE_KEY, $movie->id);
                }
            }
        });
    }
}
