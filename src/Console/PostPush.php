<?php

namespace Haxibiao\Media\Console;

use App\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PostPush extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature   = 'post:push {--hasMovie:已关联电影}';
    public const CACHE_KEY = "post_push_last_id";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '将本APP采集视频（post）的数据同步到media数据中心';

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
        $maxid = Cache::get(self::CACHE_KEY, 0);
        $count = 0;
        $qb    = Post::query()->where('id', '>', $maxid);

        if ($this->option('hasMovie')) {
            $qb = $qb->has('movies');
        }

        $qb->chunk(100, function ($posts) use (&$count) {
            foreach ($posts as $post) {
                Cache::put(self::CACHE_KEY, $post->id);
                if (!isset($post->video->hash)) {
                    continue;
                }
                $this->info("开始处理动态" . $post->description);

                //根据video hash判断是否已存在media
                $mediaVideo = DB::connection('media')->table('videos')->where([
                    'hash' => $post->video->hash,
                ]);

                $movie_key = $post->movie->region . "_" . $post->movie->id;
                //存在直接修改数据
                if (!$mediaVideo->exists()) {
                    $this->info("没有该视频");
                    $video     = $post->video;
                    $videoPath = $video->path;
                    if (!str_contains($videoPath, 'http')) {
                        $videoPath = \Storage::url($videoPath);
                    }
                    $videoId = DB::connection('media')->table('videos')->insertGetId([
                        'path'       => $videoPath,
                        'name'       => $video->hash ?? '',
                        'duration'   => $video->duration,
                        'disk'       => $video->disk,
                        'hash'       => $video->hash,
                        'json'       => json_encode($video->json),
                        'movie_key'  => $movie_key,
                        'status'     => $video->status,
                        'client_id'  => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $this->info("视频创建成功");
                    DB::connection('media')->table('posts')->insert([
                        'description' => $post->description,
                        'video_id'    => $videoId,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                    $this->info("动态创建成功");
                    continue;
                }
                $mediaVideo->update(['movie_key' => $movie_key]);
                $this->info("修改成功" . $movie_key);
                $count++;
            }
        });
    }
}
