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
	{--db : 数据库模式}
	{--is_neihan=false}
	{--source= : 资源来源,如:内函电影,nunu}
	{--region= : 按地区}
    {--type= : 按类型}
	{--style= : 按风格}
    {--year= : 按年份}
    {--producer= : 按导演}
    {--actors= : 按演员}
    {--id= : 导的开始id}
    {--movie_name= : 指定电影名称}
    {--line= : 线路简称,如nunu,kkw}';

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
        if ($this->option('db')) {
            $this->database();
        } else {
            $this->api();
        }
        return 0;
    }

    public function api()
    {
        $success = 0;
        $fail    = 0;
        $total   = 0;
        $page    = 1;

        $returnCount = 0;
        $nunu_count  = 0;
        $kkw_count   = 0;

        $args = array_except($this->options(), ['way']);
        do {
            data_set($args, 'page', $page);
            $requestArgs = http_build_query($args);
            $url         = get_neihancloud_api() . "/api/resource/list/" . '?' . $requestArgs;
            $result      = json_decode(file_get_contents($url), true);
            $returnCount = count($result['data']);
            if ($result['status'] == 200) {
                $resultMovies = $result['data'];
                foreach ($resultMovies as $movie) {
                    $total++;
                    $this->syncMovie($movie, $success, $fail, $nunu_count, $kkw_count);
                }
                $page++;
            }
        } while ($returnCount >= 300);
        $this->info('共检索出' . $total . '部电影,成功导入：' . $success . '部,失败：' . $fail . '部' . ' nunu:' . $nunu_count . ' kkw:' . $kkw_count);
    }

    public function database()
    {
        if (env('DB_HOST_MEDIACHAIN') == null) {
            $db_password_media = $this->ask("请输入内涵云DB_HOST, 或者[enter]跳过");
            if ($db_password_media) {
                config(['database.connections.mediachain.host' => $db_password_media]);
            }
        }

        if (env('DB_PASSWORD_MEDIA') == null) {
            $db_password_media = $this->ask("请输入内涵云DB_PASSOWRD, 或者[enter]跳过");
            if ($db_password_media) {
                config(['database.connections.mediachain.password' => $db_password_media]);
            }
        }

        $success    = 0;
        $fail       = 0;
        $total      = 0;
        $nunu_count = 0;
        $kkw_count  = 0;

        $qb = DB::connection('mediachain')->table('movies')
            ->where('status', '>=', 0) //只同步未删除的
            ->when($line = $this->option('line'), function ($q) use ($line) {
                //快速同步指定线路的更新
                $q->whereNotNull($line . '_source');
            })
            ->when($source = $this->option('source'), function ($q) use ($source) {
                //指定来源
                $q->where('source', $source);
            })
            ->when($is_neihan = data_get($this->options(), 'is_neihan'), function ($q) use ($is_neihan) {
                $q->where('is_neihan', $is_neihan !== 'false');
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
                $this->syncMovie($movie, $success, $fail, $nunu_count, $kkw_count);
            }
        });
        $this->info('共检索出' . $total . '部电影,成功导入：' . $success . '部,失败：' . $fail . '部' . ' nunu:' . $nunu_count . ' kkw:' . $kkw_count);
    }

    public function syncMovie($movie, &$success, &$fail, &$nunu_count, &$kkw_count)
    {
        DB::beginTransaction();
        try {
            //未处理好source_key之前，先按 name 和 producer 排重导入
            $movie = @json_decode(json_encode($movie), true);
            $model = Movie::firstOrNew([
                'name'     => $movie['name'],
                'name'     => $movie['name'],
                'producer' => $movie['producer'],
            ]);

            $movieExists = $model->id > 0;

            $movie['producer'] = str_limit($movie['producer'], 97, '...');
            $movie['actors']   = str_limit($movie['actors'], 97, '...');
            //剔除简介html代码
            $movie['introduction'] = strip_tags($movie['introduction'] ?? '');
            //同步type
            $movie['type'] = $movie['type_name'];

            //默认线路
            $default_sereies = @json_decode($movie['data'], true) ?? [];
            $movie['data']   = $default_sereies;

            //修复count_series
            $movie['count_series'] = count($default_sereies);

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
                $series = @json_decode($movie['data_source'], true) ?? [];
                if (count($series)) {
                    $other_source['麻花云'] = $series;
                }
            }
            $has_nunu = false;
            if (isset($movie['nunu_source'])) {
                $series = @json_decode($movie['nunu_source'], true) ?? [];
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
                $series = @json_decode($movie['kkw_source'], true) ?? [];
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

            $model->forceFill(array_only($movie, [
                'status',
                'source',
                'source_key',
                'movie_key',
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
            if ($has_nunu) {
                $addOrUpdate .= "(nunu)";
                $nunu_count++;
            }
            if ($has_kkw) {
                $addOrUpdate .= "(kkw)";
                $nunu_count++;
            }
            if ($has_cokemv) {
                $addOrUpdate .= "(cokemv)";
                $nunu_count++;
            }
            $this->info('已成功：' . $success . '部, 当前' . $addOrUpdate . ':' . data_get($movie, 'region') . '-' . data_get($movie, 'name') . " - (" . $model->count_series . ")集" . $model->id . ' - ' . data_get($movie, 'movie_key'));
        } catch (\Throwable $th) {
            DB::rollback();
            $fail++;
            $this->error('导入失败：' . $fail . '部, 电影名:' . data_get($movie, 'name'));
        }
    }
}
