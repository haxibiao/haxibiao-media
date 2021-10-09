<?php

namespace Haxibiao\Media\Console;

use App\EditorChoice;
use App\Stick;
use Haxibiao\Media\Actor;
use Haxibiao\Media\Director;
use Haxibiao\Media\Movie;
use Haxibiao\Media\MovieActor;
use Haxibiao\Media\MovieDirector;
use Haxibiao\Media\MovieRegion;
use Haxibiao\Media\MovieSource;
use Haxibiao\Media\MovieType;
use Haxibiao\Media\Region;
use Haxibiao\Media\Type;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class StickSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stick:sync {--db : 数据库模式}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '同步电影数据到合集';

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
        if (!Schema::hasTable('movies')) {
            return $this->error("当前数据库 没有movies表!");
        }
        $this->info("执行前请确保先movie:sync");
        $this->database();

        return 0;
    }

    public function database()
    {
        //合集分类
        $qb = DB::connection('juhaokantv')->table('editor_choices')->chunkById(100, function ($editorChoices) {
            foreach ($editorChoices as $editorChoice) {
                try {
                    $this->info("开始同步精选【{$editorChoice->title}】数据");
                    //创建精选
                    $editorChoice = EditorChoice::firstOrCreate([
                        'editor_id' => 1,
                        'title'     => $editorChoice->title,
                        'summary'   => $editorChoice->summary,
                    ]);

                    $movie_ids = DB::connection('juhaokantv')->table('choiceables')
                        ->select('choiceable_id')
                        ->where('editor_choice_id', $editorChoice->id)
                        ->where('choiceable_type', 'movies')
                        ->pluck('choiceable_id')->toArray();

                    $movie_keys = DB::connection('juhaokantv')->table('movies')
                        ->whereIn('id', $movie_ids)
                        ->pluck('movie_key')
                        ->toArray();
                    $this->info("获取同步电影movie_key成功...");

                    //同步sticks movies数据
                    foreach ($movie_ids as $movie_id) {
                        $movie = DB::connection('juhaokantv')->table('movies')->find($movie_id);
                        $this->syncMovie($movie);
                    }

                    //获取movie_ids
                    $movie_ids = Movie::whereIn('movie_key', $movie_keys)->pluck('id')->toArray();
                    //保存关系
                    $editorChoice->movies()->sync($movie_ids);
                    $this->info("精选电影关系保存成功！！！");

                    $stick = DB::connection('juhaokantv')
                        ->table('sticks')
                        ->where('editor_choice_id', $editorChoice->id)
                        ->first();

                    if ($stick) {
                        $this->info("开始同步置顶信息....");
                        $model = new Stick();
                        $model->forceFill(array_only((array) $stick, [
                            'editor_choice_id',
                            'place',
                            'rank',
                            'cover',
                            'editor_id',
                        ]))->save();
                    }

                } catch (\Exception$e) {
                    Log::error($e);
                    dd("同步异常！！！");
                }
            }
            $this->info("同步完成....");
        });
    }

    public function syncMovie($movie)
    {
        DB::beginTransaction();
        try {
            //未处理好source_key之前，先按 name 和 directors 排重导入
            $movie = @json_decode(json_encode($movie), true);
            $model = Movie::firstOrNew([
                'name'     => $movie['name'],
                'producer' => $movie['producer'] ?? $movie['directors'],
            ]);

            if ($model->id) {
                $this->info("存在影片，跳过");
                return;
            }
            $movieExists = $model->id > 0;

            //修复国家
            $country          = $movie['country'] ?? $movie['region'] ?? null;
            $movie['country'] = $country;
            //修复region
            $region = '其他';
            if ($country) {
                if (str_contains($country, '美国')) {
                    $region = '美剧';
                }
                if (str_contains($country, '韩国')) {
                    $region = '韩剧';
                }
                if (str_contains($country, '日本')) {
                    $region = '日剧';
                }
                if (str_contains($country, '香港') || str_contains($country, '台湾')) {
                    $region = '港剧';
                }
                if (str_contains($country, '中国') || str_contains($country, '大陆') || str_contains($country, '国产')) {
                    $region = '国产';
                }
            }
            $movie['region'] = $region;

            $movie['producer'] = str_limit($movie['producer'] ?? $movie['directors'], 97, '...');
            $movie['actors']   = str_limit($movie['actors'], 97, '...');
            //剔除简介html代码
            $movie['introduction'] = strip_tags($movie['introduction'] ?? '');
            //同步type
            $movie['type'] = $movie['type_name'] ?? $movie['type'];

            $default_sereies = @json_decode($movie['data'], true) ?? [];
            $movie['data']   = $default_sereies;

            //修复count_series
            $movie['count_series'] = count($default_sereies);
            $movie['status']       = 1;

            //空的不覆盖已有的
            if (empty($movie['cover'])) {
                $movie['cover'] = $model->cover;
            }
            if (empty($movie['source_key'])) {
                $movie['source_key'] = $model->source_key;
            }

            //其他线路
            $other_source = ['默认' => $default_sereies];

            //内涵云早期新增影片时源线路
            if (isset($movie['data_source'])) {
                $series = $movie['data_source'] ?? [];
                if (count($series)) {
                    $other_source['麻花云'] = $series;
                }
            }
            $has_nunu = false;
            if (isset($movie['nunu_source'])) {
                $series = $movie['nunu_source'] ?? [];
                if (count($series)) {
                    //有nunu的可以优先尊重,覆盖默认
                    $movie['data']             = $series;
                    $movie['count_series']     = count($series);
                    $other_source['努努云'] = $series;
                    $has_nunu                  = true;
                }
            }
            $has_kkw = false;
            if (isset($movie['kkw_source'])) {
                $series = $movie['kkw_source'] ?? [];
                if (count($series)) {
                    //默认的没有或者不全，可以用看看屋的做默认
                    if (count($series) > count($default_sereies)) {
                        $movie['data']         = $series;
                        $movie['count_series'] = count($series);
                    }
                    $other_source['看看屋'] = $series;
                    $has_kkw                   = true;
                }
            }

            $has_cokemv = false;
            if (isset($movie['cokemv_source'])) {
                $series = @json_decode($movie['cokemv_source'], true) ?? [];
                if (count($series)) {
                    //默认的没有或者不全，可以用看看屋的做默认
                    if (count($series) > count($default_sereies)) {
                        $movie['data']         = $series;
                        $movie['count_series'] = count($series);
                    }
                    $other_source['cokemv'] = $series;
                    $has_cokemv             = true;
                }
            }

            $movie['data_source'] = $other_source;

            // $play_lines = [];
            // //获取影片线路 - movie_sources
            $sources = $movie['available_sources'] ?? null;
            if (empty($sources)) {
                $sources = DB::connection('mediachain')->table('movie_sources')
                    ->where('movie_id', $movie['id'])->get();
                $sources = @json_decode(json_encode($sources), true);
            }

            // $sources = $movie['available_sources'];
            // if(count($sources) < 0){
            //     $play_lines = [];
            // }else{
            //     foreach($sources as $source){
            //         $play_lines[] = [
            //             'name'      => $source['name'],
            //             'url'       => $source['url'],
            //             'data'      => $source['play_urls'],
            //         ];
            //     }
            // }
            // $movie['play_lines']  = $play_lines;

            self::fillMovieModel($movie, $model);
            self::createRelationModel($model);

            //同步保存影片线路数据
            self::saveMovieSources($sources, $model);

            DB::commit();
            $addOrUpdate = $movieExists ? '更新' : '新增';
            if ($has_nunu) {
                $addOrUpdate .= "(nunu)";
            }
            if ($has_kkw) {
                $addOrUpdate .= "(kkw)";
            }
            if ($has_cokemv) {
                $addOrUpdate .= "(cokemv)";
            }
            $this->info('已成功, 当前' . $addOrUpdate . ':' . data_get($movie, 'region') . '-' . data_get($movie, 'name') . " - (" . $model->count_series . ")集" . $model->id . ' - ' . data_get($movie, 'movie_key'));
        } catch (\Throwable$th) {
            DB::rollback();
            dd($th);
            $this->error('导入失败, 电影名:' . data_get($movie, 'name'));
        }
    }

    public static function fillMovieModel(array $movie, Movie $model)
    {
        if ($movie['custom_type'] == '电影') {
            $movie['finished'] = 1;
        }

        $model->forceFill(array_only($movie, [
            'source',
            'source_key',
            'movie_key',
            'introduction',
            'cover',
            'producer',
            'year',
            'region',
            'actors',
            'rank',
            'country',
            'subname',
            'score',
            'tags',
            'hits',
            'lang',
            'type',
            'data',
            'finished',
            'has_playurl',
            'custom_type',
            // 'play_lines',
            'source_names',
        ]));
        $model->saveQuietly();
        return $model;
    }

    public static function saveMovieSources($sources, $model)
    {
        foreach ($sources as $source) {

            $movieSource = MovieSource::firstOrNew([
                'name' => $source['name'],
                'url'  => $source['url'],
            ]);
            $movieSource->movie_id = $model->id;
            $movieSource->rank     = $source['rank'];
            $play_lines            = $source['play_urls'];
            if (is_string($play_lines)) {
                $play_lines = json_decode($play_lines, true);
            }
            $movieSource->play_urls  = $play_lines;
            $movieSource->remark     = $source['remark'];
            $movieSource->created_at = now();
            $movieSource->updated_at = now();
            $movieSource->save();
        }
    }

    public static function createRelationModel(Movie $movie)
    {
        $region = $movie->region;
        if (!empty($region)) {
            $regions = explode(',', $region);
            foreach ($regions as $item) {
                $regionModel = Region::firstOrCreate(['name' => $item]);
                MovieRegion::firstOrCreate([
                    'movie_id'  => $movie->id,
                    'region_id' => $regionModel->id,
                ]);
            }
        }

        $actor = $movie->actors;
        if (!empty($actor)) {
            $actors = explode(',', $actor);
            foreach ($actors as $item) {
                $actorModel = Actor::firstOrCreate(['name' => $item]);
                MovieActor::firstOrCreate([
                    'movie_id' => $movie->id,
                    'actor_id' => $actorModel->id,
                ]);
            }
        }

        $director = $movie->producer;
        if (!empty($director)) {
            $directors = explode(',', $director);
            foreach ($directors as $item) {
                $directorModel = Director::firstOrCreate(['name' => $item]);
                MovieDirector::firstOrCreate([
                    'movie_id'    => $movie->id,
                    'director_id' => $directorModel->id,
                ]);
            }
        }

        $type = $movie->type;
        if (!empty($type)) {
            $types = explode(',', $type);
            foreach ($types as $item) {
                $typeModel = Type::firstOrCreate(['name' => $item]);
                MovieType::firstOrCreate([
                    'movie_id' => $movie->id,
                    'type_id'  => $typeModel->id,
                ]);
            }
        }
    }
}
