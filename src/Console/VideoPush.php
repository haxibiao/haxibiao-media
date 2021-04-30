<?php

namespace Haxibiao\Media\Console;

use Haxibiao\Content\Category;
use Haxibiao\Content\Post;
use Haxibiao\Media\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class VideoPush extends Command
{
    /**
     *
     * @var string
     */
    protected $signature                  = 'video:push {--db} {--api}';
    public const CACHE_KEY                = "video_sync_max_id";
    protected const HAXIYUN_VIDEO         = 'http://media.haxibiao.com/api/v1/hashvod/videos/';
    protected const HAXIYUN_UPDATED_VIDEO = 'http://media.haxibiao.com/api/video/update';

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

    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        //push采用的方法。默认sql方式
        if ($this->option('api')) {
            $method = 'apiPush';
        } else {
            $method = 'dbPush';
        }

        // 从小到大push数据
        $qb = Video::query()->oldest('id')->whereStatus(Video::COVER_VIDEO_STATUS);

        $maxid = Cache::rememberForever($this->appName . self::CACHE_KEY, function () use ($qb) {
            return $qb->min('id');
        });
        // 跳过已push的数据，从上次结束的地方开始push
        if ($maxid) {
            $qb->where('id', '>=', $maxid);
        }

        $this->info("起步video id = " . $maxid);
        $this->info("总计本次需同步视频数 = " . $qb->count());

        $qb->chunk(500, function ($videos) use ($method) {
            foreach ($videos as $video) {
                Cache::put($this->appName . self::CACHE_KEY, $video->id);

                //确保视频已完整上传
                if (!isset($video->hash)) {
                    $this->info('该视频hash不存在，跳过同步');
                    continue;
                }

                //需要本地已发布成功通过审核
                $post = $video->post;
                if (!isset($post) || $post->status < Post::PUBLISH_STATUS) {
                    $this->info('该视频在post尚未发布，跳过同步');
                    continue;
                }

                //需要分类和合集关系正常
                if (!isset($post->category) && !isset($post->collection)) {
                    $this->info('该视频没有合集和分类信息，跳过同步');
                    continue;
                }
                //excute push video
                $this->$method($video);

            }

        });
    }

    /**
     * api方式push
     */
    public function apiPush($video)
    {

        // API方式查询是否video已存在
        $queryUrl       = self::HAXIYUN_VIDEO . $video->hash;
        $queryResult    = json_decode(file_get_contents($queryUrl), true);
        $qbMediaVideoId = isset($queryResult['data']) ?: data_get($queryResult, 'data.id', 0);

        //不存在则不需要同步 meta信息
        if (!$qbMediaVideoId) {
            $this->info('该视频在media中不存在，跳过同步');
            return;
        }

        //api方式更新media中的video信息
        $args = [];
        if (isset($video->post->collection)) {
            $args['collection']     = $video->post->collection->name;
            $args['collection_key'] = $this->appName . "_" . $video->post->collection_id;
        }
        if (isset($video->post->category)) {
            // 哈希云中的videos还没增加category字段
            $args['category'] = $video->post->category->name;
        }
        $args['source'] = $this->appName;
        $args['hash']   = $video->hash;
        $requestArgs    = http_build_query($args);

        $updateUrl      = self::HAXIYUN_UPDATED_VIDEO . '?' . $requestArgs;
        $queryResult    = json_decode(file_get_contents($updateUrl), true);
        $qbMediaVideoId = isset($queryResult->data) ? $queryResult->data->id : 0;

        $this->info("同步视频{$video->id}数据到 media.haxibiao.com 成功");

    }

    /**
     * sql方式push 需要先配置好media的数据库配置
     *
     */
    public function dbPush($video)
    {
        //根据video hash判断是否已存在
        $qbMediaVideoId = Video::on('media')->where([
            'hash' => $video->hash,
        ])->pluck('id')->first();

        $post = $video->post;
        //不存在则不需要同步 meta信息
        if (!$qbMediaVideoId) {
            info($qbMediaVideoId);
            $this->info('该视频在media中不存在，跳过同步');
            return;
        }

        //media中心只维护videos和他的meta字段，不维护posts categories关系表
        //同步media中videos数据的 collection 和 category 字段（取一个作为主要的）
        $videoOnMedia = Video::on('media')->find($qbMediaVideoId);
        if ($videoOnMedia) {

            if (isset($post->collection)) {
                $videoOnMedia->collection     = $post->collection->name;
                $videoOnMedia->collection_key = $this->appName . "_" . $post->collection_id;
            }
            if (isset($post->category)) {
                // 哈希云中的videos还没增加category字段
                $videoOnMedia->category = $post->category->name;
            }
            $videoOnMedia->source = $this->appName;
            $videoOnMedia->save();

        }

        $this->info("同步视频{$video->id}数据到 media.haxibiao.com 成功");
    }
}
