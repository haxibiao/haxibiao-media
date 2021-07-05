<?php

namespace Haxibiao\Media\Console;

use Haxibiao\Content\Collection;
use Haxibiao\Media\Movie;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CollectionMovieSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'collection:movie:sync {--db : 数据库模式}';

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
        if ($this->option('db')) {
            $this->database();
        } else {
            dd("api方式先不支持");
        }
        return 0;
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

        //合集分类
        $qb = DB::connection('mediachain')->table('collects')->chunkById(100, function ($collects) {
            foreach ($collects as $collect) {
                try {
                    $this->info("开始同步合集【{$collect->name}】数据");

                    //创建合集
                    $collection = Collection::firstOrCreate([
                        'user_id' => 1,
                        'type'    => 'movie',
                        'name'    => $collect->name,
                    ]);

                    //中间表关联数据不多，一口气全拿出来吧
                    $movie_ids = DB::connection('mediachain')->table('movie_collects')
                        ->where('collect_id', $collect->id)
                        ->pluck('movie_id')
                        ->toArray();
                    $this->info("获取同步电影id成功...");

                    //拿到movie_key
                    $movie_keys = DB::connection('mediachain')
                        ->table('movies')
                        ->whereIn('id', $movie_ids)
                        ->pluck('movie_key')
                        ->toArray();
                    $this->info("获取同步电影movie_key成功...");

                    $movie_ids = Movie::whereIn('movie_key', $movie_keys)->pluck('id')->toArray();
                    //保存关系
                    $collection->recollect($movie_ids, 'movies', false);
                    $this->info("电影合集关系保存成功！！！");
                } catch (\Exception $e) {
                    Log::error($e);
                }
            }
        });
    }
}
