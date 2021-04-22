<?php

namespace Haxibiao\Media\Console;

use Haxibiao\Content\Category;
use Haxibiao\Content\Post;
use Haxibiao\Media\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class VideoPush extends Command
{
    /**
     * FIXME: 暂时没空测试，video:publish 应该是这样写，只专注Push videos的元数据信息回来哈希云，方便后面的 video:sync 指定条件同步数，做单个APP的posts, category, collection更新策略
     *
     * @var string
     */
    protected $signature   = 'video:push';
    public const CACHE_KEY = "video_sync_max_id";

    public const DEFAULT_CATE_NAME = "有趣短视频";

    protected $appName;
    protected $defaultCategory;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '将本APP粘贴、剪辑的视频(不处理post,category,collection等数据维护，meta信息即可)同步到media中心';

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
        $categoryName          = data_get(config('applist'), $this->appName . '.category_name');
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
        $qb = Video::query()->oldest('id')->whereStatus(Video::CDN_VIDEO_STATUS);

        $maxid = Cache::rememberForever($this->appName . self::CACHE_KEY, function () use ($qb) {
            return $qb->min('id');
        });
        // 跳过已push的数据，从上次结束的地方开始push
        if ($maxid) {
            $qb->where('id', '>=', $maxid);
        }

        $this->info("起步video id = " . $maxid);
        $this->info("总计本次需同步视频数 = " . $qb->count());

        $qb->chunk(1000, function ($videos) {
            foreach ($videos as $video) {
                Cache::put($this->appName . self::CACHE_KEY, $video->id);

                //确保视频已完整上传
                if (!isset($video->hash)) {
                    continue;
                }
                //根据video hash判断是否已存在
                $qbMediaVideo = DB::connection('media')->table('videos')->where([
                    'hash' => $video->hash,
                ]);
                $existsOnMedia = $qbMediaVideo->exists();

                //不存在则不需要同步 meta信息
                if (!$existsOnMedia()) {
                    continue;
                }

                //需要本地已发布成功通过审核
                $post = $video->post;
                if ($post->status < Post::PUBLISH_STATUS) {
                    continue;
                }

                //需要分类和合集关系正常
                if (!isset($post->category) || !isset($post->collection)) {
                    continue;
                }

                //不搞默认分类了，等于没分类，没意义

                //media中心只维护videos和他的meta字段，不维护posts categories关系表

                //同步media中videos数据的 collection 和 category 字段（如果多个，逗号分开）
                if ($videoOnMedia = $qbMediaVideo->first()) {

                    $videoOnMedia->collection     = $post->collection->name;
                    $videoOnMedia->collection_key = $this->appName . "_" . $post->collection_id;

                    // 哈希云中的videos还没增加category字段
                    $videoOnMedia->category = $post->category->name;
                    $videoOnMedia->save();
                }

                $this->info("同步视频{$video->id}数据到 media.haxibiao.com 成功");
            }
        });
    }
}
