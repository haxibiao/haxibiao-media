<?php

namespace Haxibiao\Media\Console;

use Haxibiao\Media\Movie;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 同步内涵云长视频数据
 * 文档地址： http: //neihancloud.com/movie/
 */
class MovieSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'movie:sync
	{--way=api}
	{--is_neihan=false}
	{--source=内函电影 : 资源来源}
	{--region= : 按地区}
    {--type= : 按类型}
	{--style= : 按风格}
    {--year= : 按年份}
    {--producer= : 按导演}
    {--actors= : 按演员}
    {--id= : 导的开始id}
    {--movie_name= : 指定电影名称}
    {--copyright=true}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '同步最新mediachain电影';

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
        $way = $this->option('way');
        $this->$way();
    }

    public function api()
    {
        $success = 0;
        $fail    = 0;
        $total   = 0;
        $args    = array_except($this->options(), ['way']);
        $page    = 1;

        $returnCount = 0;
        $nunu_count  = 0;

        do {
            data_set($args, 'page', $page);
            $requestArgs = http_build_query($args);
            $url         = "https://mediachain.info/api/resource/list/" . '?' . $requestArgs;
            $result      = json_decode(file_get_contents($url), true);
            $returnCount = count($result['data']);
            if ($result['status'] == 200) {
                $resultMovies = $result['data'];
                foreach ($resultMovies as $movie) {
                    $total++;
                    $this->syncMovie($movie, $success, $fail, $nunu_count);
                }
                $page++;
            }
        } while ($returnCount >= 300);
        $this->info('共检索出' . $total . '部电影,成功导入：' . $success . '部,失败：' . $fail . '部' . ' nunu:' . $nunu_count);
    }

    public function database()
    {
        if (env('DB_PASSWORD_MEDIA') == null) {
            $db_password_media = $this->ask("请注意 env('DB_PASSWORD_MEDIA') 未设置，正在用env('DB_PASSWORD'), 如果需要不同密码请输入或者[enter]跳过");
            if ($db_password_media) {
                config(['database.connections.mediachain.password' => $db_password_media]);
                $this->confirm("已设置media的db密码，继续吗? ");
            }
        }

        $success = 0;
        $fail    = 0;
        $total   = 0;
        $qb      = DB::connection('mediachain')->table('movies')
            ->when($is_neihan = data_get($this->options(), 'is_neihan'), function ($q) use ($is_neihan) {
                if ($is_neihan == 'false') {
                    $q->where('is_neihan', 0);
                }
            })
            ->when($id = data_get($this->options(), 'id'), function ($q) use ($id) {
                $q->where('id', '>=', $id);})
            ->when($region = data_get($this->options(), 'region'), function ($q) use ($region) {
                $q->where('region', $region);})
            ->when($type = data_get($this->options(), 'type'), function ($q) use ($type) {
                $q->where('type_name', $type);})
            ->when($style = data_get($this->options(), 'style'), function ($q) use ($style) {
                $q->where('style', $style);})
            ->when($year = data_get($this->options(), 'year'), function ($q) use ($year) {
                $q->where('year', $year);})
            ->when($producer = data_get($this->options(), 'producer'), function ($q) use ($producer) {
                $q->where('producer', $producer);})
            ->when($movie_name = data_get($this->options(), 'movie_name'), function ($q) use ($movie_name) {
                $q->where('name', $movie_name);
            })
            ->when($actors = data_get($this->options(), 'actors'), function ($q) use ($actors) {
                $actors = explode(',', $actors);
                $q->where(function ($q) use ($actors) {
                    foreach ($actors as $actor) {
                        if (!trim($actor)) {
                            continue;
                        }
                        $q = $q->orWhere('actors', 'like', '%' . $actor . '%');
                    }
                });})
            ->orderBy('id');

        $this->info('总计电影数:' . $qb->count());
        $qb->chunk(100, function ($movies) use (&$fail, &$success, &$total) {
            foreach ($movies as $movie) {
                $total++;
                $this->syncMovie($movie, $success, $fail, $nunu_count);
            }
        });
        $this->info('共检索出' . $total . '部电影,成功导入：' . $success . '部,失败：' . $fail . '部');
    }

    public function syncMovie($movie, &$success, &$fail, &$nunu_count)
    {
        DB::beginTransaction();
        try {
            //按 name 和 producer 排重导入
            $movie = @json_decode(json_encode($movie), true);
            $model = Movie::firstOrNew([
                'name'     => $movie['name'],
                // 'source'     => $this->option('source'),
                // 'source_key' => $movie['source_key'],
                'name'     => $movie['name'],
                'producer' => $movie['producer'],
            ]);

            $movieExists = $model->id > 0;

            $movie['producer']     = str_limit($movie['producer'], 97);
            $movie['actors']       = str_limit($movie['actors'], 97);
            $movie['introduction'] = $movie['introduction'] ?? '';
            //同步type
            $movie['type']   = $movie['type_name'];
            $movie['status'] = 1;

            //默认线路
            $default_sereies = @json_decode($movie['data'], true) ?? [];
            $movie['data']   = $default_sereies;

            //修复count_series
            $movie['count_series'] = count($default_sereies);

            //封面修复
            if (empty($movie['cover'])) {
                //空的不覆盖已有的
                $movie['cover'] = $model->cover;
            }

            //其他线路
            $other_source = ['默认' => $default_sereies];

            //内涵云早期新增影片时源线路
            if (isset($movie['data_source'])) {
                $series = @json_decode($movie['data_source'], true) ?? [];
                if (count($series)) {
                    $other_source['麻花云'] = $series;
                }
            }
            $hasNunu = false;
            if (isset($movie['nunu_source'])) {
                //有nunu的可以优先尊重做默认
                $series = @json_decode($movie['nunu_source'], true) ?? [];
                dd($series);
                if (count($series)) {
                    $movie['data'] = $series;
                    //努努云线路
                    $other_source['努努云'] = $series;
                    $hasNunu                   = true;
                }
            }
            $movie['data_source'] = $other_source;

            $model->forceFill(array_only($movie, [
                'status',
                'introduction',
                'cover',
                'producer',
                'year',
                'region',
                'actors',
                'miner',
                'count_series',
                'rank',
                'country',
                'subname',
                'score',
                'tags',
                'hits',
                'lang',
                'type',
                'data',
                'data_source',
            ]));
            $model->status = Movie::PUBLISH;
            $model->saveQuietly();
            DB::commit();
            $success++;
            $addOrUpdate = $movieExists ? '更新' : '新增';
            if ($hasNunu) {
                $addOrUpdate .= "(nunu)";
                $nunu_count++;
            }
            $this->info('已成功：' . $success . '部, 当前' . $addOrUpdate . ':' . data_get($movie, 'region') . '-' . data_get($movie, 'name') . " - (" . $model->count_series . ")集" . $model->id . ' - ' . data_get($movie, 'id'));
        } catch (\Throwable $th) {
            DB::rollback();
            $fail++;
            $this->error('导入失败：' . $fail . '部, 电影名:' . data_get($movie, 'name'));
        }
    }
}
