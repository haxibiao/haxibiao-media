<?php

namespace Haxibiao\Media\Console;

use App\Post;
use App\Video;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class VideoSync extends Command
{

    /**
     * 导入的post会随机分配到系统马甲号上，如果没有马甲号，则会导入失败
     */

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'video:sync  {--category=: 视频分类} {--collection=: 视频合集:如：影视剪辑} {--source= : 来源，如yinxiangshipin} {--categorized : 只取有分类的视频}{--collectable : 只取有合集的视频}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description           = '按要求同步视频数据';
    protected const HAXIYUN_ENDPOINT = 'http://media.haxibiao.com/api/video/list';
    protected const COSV5_CDN        = 'http://hashvod-1251052432.file.myqcloud.com';

    protected $client;
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->client = new Client();

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
                config(['database.connections.media.password' => $db_password_media]);
                $this->confirm("已设置media的db密码，继续吗? ");
            }
        }

        $this->info("拉取media上的数据成功....");
        $results = $this->getUrlResponse($this->getArgs());
        if (data_get($results, 'success')) {
            $this->info("待拉取数据:" . data_get($results, 'meta.total') . '正在导入第' . data_get($results, 'meta.current_page'));

        } else {
            $this->error("media数据查询失败");
            return;
        }
        $videos       = data_get($results, 'data');
        $last_page    = data_get($results, 'meta.last_page');
        $current_page = data_get($results, 'meta.current_page');

        for (; $current_page < $last_page;) {

            foreach ($videos as $video) {

                $this->comment("开始导入 $video->id $video->author : $video->description $video->path $video->cover");

                //排重
                if (Video::where('hash', $video->hash)->exists()) {
                    $this->warn('source_id' . $video->id . $video->description . '该video已存在，跳过');
                    continue;
                }

                $duration = null;
                if ($json = @json_decode($video->json)) {
                    $duration = intval($json->duration ?? 0); //时长 秒
                }
                $newVideo = Video::create([
                    'user_id'  => 1, //视频的作者id不重要
                    'title'    => $video->description, //视频配文
                    'path'     => $video->path,
                    'duration' => $duration,
                    'hash'     => $video->hash,
                    'cover'    => $video->cover,
                    'status'   => $video->status,
                    'json'     => $video->json,
                    'disk'     => $video->disk,
                ]
                );
                $this->info('成功导入视频' . $newVideo->id);
            }

        }
    }

    public function getUrlResponse($args, $url = self::HAXIYUN_ENDPOINT)
    {
        $response = $this->client->request('GET', $url, [
            'http_errors' => false,
            'query'       => $args,
        ]);
        $contents = $response->getBody()->getContents();
        return $contents;

    }

    public function getArgs()
    {
        $collectable = $this->option('collectable');
        $categorized = $this->option('categorized');
        $source      = $this->option('source');
        $collection  = $this->option('collection');
        $category    = $this->option('category');
        $args        = [];
        $page        = 1;
        if ($collectable) {
            $args['collectable'] = $collectable;
        }
        if ($categorized) {
            $args['categorized'] = $categorized;
        }
        if ($source) {
            $args['source'] = $source;
        }
        if ($collection) {
            $args['collection'] = $collection;
        }
        if ($category) {
            $args['category'] = $category;
        }
        return $args;
    }
}
