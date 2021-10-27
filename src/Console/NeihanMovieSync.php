<?php

namespace Haxibiao\Media\Console;

use Haxibiao\Media\Movie;
use Illuminate\Console\Command;

/**
 * 同步内涵云内涵长视频数据
 * 文档地址： http://neihancloud.com/movie/
 */
class NeihanMovieSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'neihan:sync
    {--only_updated= : 是否只同步当天更新的影片}
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
    protected $description = '同步最新mediachain内函电影';

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
        $this->api();
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
        $args        = array_except($this->options(), ['way']);
        $args        = array_filter($args);
        do {
            data_set($args, 'page', $page);
            $requestArgs = http_build_query($args);
            $url         = get_neihancloud_api() . "/api/neihan/list/" . '?' . $requestArgs;
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

    public function syncMovie($movie, &$success, &$fail, &$nunu_count, &$kkw_count)
    {

        try {
            $movie = @json_decode(json_encode($movie), true);
            $model = Movie::firstOrNew([
                'movie_key' => $movie['movie_key'],
            ]);

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
            $movie['type'] = $movie['type_name'] ?? $movie['types'];

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

            //播放线路这个属性前端不支持null
            $movie['play_lines'] = $movie['play_lines'] ?? [];

            self::fillMovieModel($movie, $model);

            $success++;
            $addOrUpdate = $movieExists ? '更新' : '新增';
            $this->info('已成功：' . $success . '部, 当前' . $addOrUpdate . ':' . data_get($movie, 'region') . '-' . data_get($movie, 'name') . " - (" . $model->count_series . ")集" . $model->id . ' - ' . data_get($movie, 'movie_key'));
        } catch (\Throwable$th) {
            dd($th);
            $fail++;
            $this->error('导入失败：' . $fail . '部, 电影名:' . data_get($movie, 'name'));
        }
    }

    public static function fillMovieModel(array $movie, Movie $model)
    {
        if ($movie['custom_type'] == '电影') {
            $movie['finished'] = 1;
        }

        $model->forceFill(array_only($movie, [
            'name',
            'source',
            'source_key',
            'movie_key',
            'introduction',
            'cover',
            'producer',
            'year',
            'play_lines',
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
            'play_lines',
            'source_names',
        ]));
        $model->saveQuietly();
        return $model;
    }

}
