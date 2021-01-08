<?php

namespace Haxibiao\Media\Console;

use Haxibiao\Content\Category;
use Haxibiao\Content\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class VideoPush extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'video:push {--defaultCate=1 : 是否采用默认分类数据}';
    public const CACHE_KEY = "video_sync_max_id";

    public const DEFAULT_CATE_NAME = "有趣短视频";

    protected $appName;
    protected $defaultCategory;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '将本APP采集视频（post）的分类数据同步到media数据中心';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->appName = config('app.name');
        //获取根据APP划分视频分类名
        $categoryName = data_get(config('applist'), $this->appName . '.category_name');
        $this->defaultCategory = $categoryName ?: self::DEFAULT_CATE_NAME;

    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        // 从小到大push数据
        $qb = Post::query()->oldest('id')->whereNotNull('video_id');

        $maxid = Cache::rememberForever($this->appName . self::CACHE_KEY, function () {
            return Post::first()->id;
        });
        // 跳过已push的数据，从上次结束的地方开始push
        if ($maxid) {
            $qb->where('id', '>', $maxid);
        }
        $isDefaultCate = $this->option('defaultCate');

        info($maxid);
        $qb->chunk(1000, function ($posts) use ($isDefaultCate) {
            foreach ($posts as $post) {
                Cache::put($this->appName . self::CACHE_KEY, $post->id);
                //同步category分类数据,只处理media已有的post，暂不推送新的post
                if (!isset($post->video->hash)) {
                    continue;
                }
                //根据video hash判断是否已存在
                $mediaVideo = DB::connection('media')->table('videos')->where([
                    'hash' => $post->video->hash,
                ]);

                //不存在则不需要同步分类
                if (!$mediaVideo->exists()) {
                    continue;
                }

                if (!$isDefaultCate && !isset($post->category)) {
                    continue;
                }
                //获取视频在源APP的category
                $categoryName = isset($post->category) ? $post->category->name : $this->defaultCategory;
                info($categoryName);
                $mediaPost = DB::connection('media')->table('posts')->where([
                    'video_id' => $mediaVideo->first()->id,
                ])->first();
                if (empty($mediaPost)) {
                    continue;
                }
                $mediaCategory = Category::on('media')->firstOrCreate([
                    'name' => $categoryName,
                ], [
                    'cover' => 'http://haxibiao-1251052432.cos.ap-guangzhou.myqcloud.com/images/collection.png',
                ]
                );

                //同步post_categories数据
                DB::connection('media')->table('post_categories')->insert([
                    'post_id' => $mediaPost->id,
                    'category_id' => $mediaCategory->id,
                ]);
                $this->info("同步post{$post->id}数据到 media.haxibiao.com 成功");
            }
        });
    }
}
