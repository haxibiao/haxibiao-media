<?php

namespace Haxibiao\Media\Console;

use App\Post;
use App\Video;
use Exception;
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
    protected $signature = 'video:sync  {--category= : 视频分类} {--collection= : 视频合集:如：影视剪辑} {--source= : 来源，如yinxiangshipin} {--categorized= : 只取有分类的视频}{--collectable= : 只取有合集的视频}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description   = '按要求同步视频数据';
    protected const CDN_ROOT = 'http://hashvod-1251052432.file.myqcloud.com';

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

        $firstData = $this->getUrlResponse($this->getArgs());

        $total = $firstData->meta->total;
        if ($firstData->success && $total > 1) {
            $this->info("拉取media上的数据成功....");
            $this->info("待拉取数据:" . $total . '条');
        } else {
            $this->error("media数据查询失败");
            return;
        }

        $videos = $firstData->data;

        $last_page    = $firstData->meta->last_page;
        $current_page = $firstData->meta->current_page;

        for ($page = $current_page; $current_page < $last_page; $page++) {

            $this->info("拉取第页" . $page . '的数据....');
            $results = $this->getUrlResponse($this->getArgs($page));

            $videos = $results->data;

            foreach ($videos as $video) {
                try {

                    $this->comment("开始导入 $video->id $video->author : $video->description $video->path $video->cover");

                    //排重
                    if (Video::where('hash', $video->hash)->exists()) {
                        $this->warn('source_id' . $video->id . $video->description . '该video已存在，跳过');
                        continue;
                    }

                    $duration = $video->duration ?: intval(@json_decode($video->json)->duration ?? 0); //时长 秒
                    $newVideo = Video::create([
                        'user_id'  => 1, //视频的作者id不重要
                        'title'    => $video->name, //视频配文
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

                } catch (Exception $e) {

                    $this->warn('source_id' . $video->id . $video->path . '该video导入失败，跳过');
                    info($e->getMessage());

                }
            }
        }
    }

    public function getUrlResponse($args)
    {
        $url      = Video::getMediaBaseUri() . 'api/video/list';
        $response = $this->client->request('GET', $url, [
            'http_errors' => false,
            'query'       => $args,
        ]);
        $contents = $response->getBody()->getContents();
        return @json_decode($contents);
    }

    public function getArgs($page = 1)
    {
        $collectable = $this->option('collectable');
        $categorized = $this->option('categorized');
        $source      = $this->option('source');
        $collection  = $this->option('collection');
        $category    = $this->option('category');
        $args        = [];
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
        $args['page'] = $page;
        return $args;
    }
}
