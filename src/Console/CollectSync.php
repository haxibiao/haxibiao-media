<?php

namespace Haxibiao\Media\Console;

use GuzzleHttp\Client;
use Haxibiao\Content\Traits\Choiceable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CollectSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'collect:sync {--db : 数据库模式}';

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

        $client   = new Client();
        $response = $client->request("GET", "https://neihancloud.com/api/collects");
        // if ($response->getStatusCode() == 200) {
        //     \App\EditorChoice::truncate();
        //     Choiceable::where('choiceable_type', 'movies')->delete();
        // }
        $collects = $response->getBody()->getContents();
        $collects = json_decode($collects, true);

        foreach ($collects['data'] as $collect) {
            //创建精选
            $editorChoice = \App\EditorChoice::firstOrCreate([
                'title' => $collect['name'],
                'rank'  => $collect['rank'],
            ]);

            //中间表关联数据不多，一口气全拿出来吧
            $movie_ids = DB::connection('mediachain')->table('movie_collects')
                ->where('collect_id', $collect['id'])
                ->pluck('movie_id')
                ->toArray();

            //拿到movie_key
            $movie_keys = DB::connection('mediachain')
                ->table('movies')
                ->whereIn('id', $movie_ids)
                ->pluck('movie_key')
                ->toArray();

            $movie_ids = \App\Movie::whereIn('movie_key', $movie_keys)->pluck('id')->toArray();
            //保存关系
            foreach ($movie_ids as $movie_id) {
                Choiceable::firstOrCreate([
                    'editor_choice_id' => $editorChoice->id,
                    'choiceable_type'  => 'movies',
                    'choiceable_id'    => $movie_id,
                ]);
            }
        }
    }
}
