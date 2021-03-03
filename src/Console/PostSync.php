<?php

namespace Haxibiao\Media\Console;

use App\Post;
use App\User;
use App\Video;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PostSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature   = 'post:sync {--hasMovie=:已关联电影} {--hasQuestin=:已关联题目}';
    public const CACHE_KEY = "post_sync_last_id";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '将media数据中心视频（post）的数据同步到本APP';

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
        $maxid = 1;
        $count = 0;
        $qb    = DB::connection('media')
            ->table('posts')
            ->select(['posts.*', 'videos.id', 'videos.path',
                'videos.duration', 'videos.disk', 'videos.hash',
                'videos.json', 'videos.collection_key', 'videos.movie_key'])
            ->where('posts.id', '>', $maxid)
            ->orderBy('posts.id', 'desc');

        if ($this->option('hasMovie') ?? null) {
            $qb = $qb->join("videos", "videos.id", "=", "posts.video_id")
                ->whereNotNUll('videos.movie_key');
            // });
        }

        $user_id = User::first()->id;
        $this->info("开始同步数据");
        $qb->chunk(100, function ($posts) use (&$count, $user_id) {
            foreach ($posts as $post) {
                // dd($post);
                DB::beginTransaction();
                try {
                    if (!isset($post->hash)) {
                        continue;
                    }
                    $this->info("开始处理动态" . $post->description);

                    //根据video hash判断是否已存在media
                    $localVideo = Video::query()->where([
                        'hash' => $post->hash,
                    ]);

                    $movie_id      = str_after($post->movie_key, "_") ?? null;
                    $collection_id = str_after($post->collection_key, "_") ?? null;

                    //不存在创建，存在直接修改数据
                    if (!$localVideo->exists()) {
                        $this->info("没有该视频");
                        $localVideo = Video::create([
                            'path'           => $post->path,
                            'title'          => $post->description,
                            'user_id'        => $user_id,
                            'duration'       => $post->duration,
                            'disk'           => $post->disk,
                            'hash'           => $post->hash,
                            'json'           => json_encode($post->json),
                            'status'         => $post->status,
                            'collection_key' => 11,
                            'created_at'     => now(),
                            'updated_at'     => now(),
                        ]);

                        $this->info("视频创建成功...");
                        Post::create([
                            'description' => $post->description,
                            'user_id'     => $user_id,
                            'video_id'    => $localVideo->id,
                            'movie_id'    => $movie_id,
                            'review_id'   => str_replace("-", "", today()->toDateString()) . substr(100001, 1, 5),
                            'review_day'  => str_replace("-", "", today()->toDateString()),
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ]);
                        $this->info("动态创建成功...");
                        Cache::put(self::CACHE_KEY, $post->id);
                        DB::commit();
                        continue;
                    }
                    $post->update(['movie_id' => $movie_id]);
                    $this->info("修改成功" . $movie_id);
                    $count++;
                    DB::commit();
                    Cache::put(self::CACHE_KEY, $post->id);
                } catch (Exception $e) {
                    $this->info("同步过程发生异常，请检查代码");
                    echo $e;
                    DB::rollBack();
                }
            }
        });
    }
}