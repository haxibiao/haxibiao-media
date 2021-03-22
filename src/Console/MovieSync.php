<?php

namespace Haxibiao\Media\Console;

use Haxibiao\Media\Movie;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MovieSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'movie:sync {--is_neihan=false} {--source=内函电影 : 资源来源} {--region= : 按地区} {--type= : 按类型} {--style= : 按风格} {--year= : 按年份} {--producer= : 按导演} {--actors= : 按演员} {--id= : 导的开始id}';

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
        if (env('DB_PASSWORD_MEDIA') == null) {
            $db_password_media = $this->ask("请注意 env('DB_PASSWORD_MEDIA') 未设置，正在用env('DB_PASSWORD'), 如果需要不同密码请输入或者[enter]跳过");
            if ($db_password_media) {
                config(['database.connections.mediachain.password' => $db_password_media]);
                $this->confirm("已设置media的db密码，继续吗? ");
            }
        }

        if (!Schema::hasTable('movies')) {
            return $this->error("没有movies表");
        }

        $region    = $this->option('region');
        $type      = $this->option('type');
        $style     = $this->option('style');
        $year      = $this->option('year');
        $producer  = $this->option('producer');
        $actors    = $this->option('actors');
        $start_id  = $this->option('id');
        $is_neihan = $this->option('is_neihan');
        $success   = 0;
        $fail      = 0;
        $total     = 0;

        $qb = DB::connection('mediachain')->table('movies')
            ->when($is_neihan, function ($q) use ($is_neihan) {
                if ($is_neihan == 'false') {
                    $q->where('is_neihan', 0);
                }
            })
            ->when($start_id, function ($q) use ($start_id) {
                $q->where('id', '>', $start_id);})
            ->when($region, function ($q) use ($region) {
                $q->where('region', $region);})
            ->when($type, function ($q) use ($type) {
                $q->where('type', $type);})
            ->when($style, function ($q) use ($style) {
                $q->where('style', $style);})
            ->when($year, function ($q) use ($year) {
                $q->where('year', $year);})
            ->when($producer, function ($q) use ($producer) {
                $q->where('producer', $producer);})
            ->when($actors, function ($q) use ($actors) {
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

        $qb->chunk(100, function ($movies) use (&$fail, &$success, &$total) {
            foreach ($movies as $movie) {
                $total++;
                $movie = @json_decode(json_encode($movie), true);
                DB::beginTransaction();
                try {
                    //按source 和 key 排重导入
                    $model = Movie::firstOrNew([
                        'name'       => data_get($movie, 'name'),
                        'source'     => $this->option('source'),
                        'source_key' => data_get($movie, 'source_key'),
                    ]);
                    //修复字段数据过长的问题
                    $movie['producer'] = str_limit($movie['producer'], 97);
                    $movie['actors']   = str_limit($movie['actors'], 97);
                    //修复count_series null引起sync出错
                    $movie['count_series'] = $movie['count_series'] ?? 0;
                    $movie['introduction'] = $movie['introduction'] ?? '';
                    //同步type
                    $movie['type'] = $movie['type_name'];
                    $movie['data']  = json_encode($movie['data']);


                    $model->forceFill(array_only($movie, [
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
                    ]))->save();
                    DB::commit();
                    $success++;
                    $this->info('已成功：' . $success . '部, 当前:' . data_get($movie, 'type') . '-' . data_get($movie, 'name') . ' - ' . data_get($movie, 'id'));
                } catch (\Exception $ex) {
                    dd($ex);
                    DB::rollback();
                    $fail++;
                    $this->error('导入失败：' . $fail . '部, 电影名:' . data_get($movie, 'name'));
                }
            }
        });
        $this->info('共检索出' . $total . '部电影,成功导入：' . $success . '部,失败：' . $fail . '部');
    }
}
