<?php

namespace Haxibiao\Media\Console;

use App\Post;
use App\Video;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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
    protected $signature = 'video:sync {--tag=: 视频标签} {--category=: 视频分类} {--source= : 来源，如印象视频} {--author= : 作者} {--endpoint= : 哈希云接口位置}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description           = '按分类同步视频数据';
    protected const HAXIYUN_ENDPOINT = 'http://media.haxibiao.com/';
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

        $qb = DB::connection('media')->table('videos')->orderBy('id');

        if ($source = $this->option('source')) {
            $qb = $qb->where('source', $source);
        }
        if ($author = $this->option('author')) {
            $qb = $qb->where('author', $author);
        }

        $count = 0;
        $qb->chunk(100, function ($videos) use (&$count) {

            foreach ($videos as $video) {

                $this->comment("开始导入 $video->id $video->author : $video->description $video->path $video->cover");

                //排重
                if (Video::where('hash', $video->hash)->exists()) {
                    $this->warn("$video->id $video->description 该video已存在，跳过");
                    continue;
                }

                $newVideo = new Video();
                $duration = null;
                if ($json = @json_decode($video->json)) {
                    $duration = intval($json->duration ?? 0); //时长 秒
                }
                $newVideo->forceFill([
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
                )->saveDataOnly();
                ++$count;
                $this->info("成功导入 $newVideo->id $newVideo->description $newVideo->path $newVideo->cover");
            }
        });
        $this->info('成功导入：' . $count . '条');
    }

    public function getUrlResponse($tag, $category, $endpiont = self::HAXIYUN_ENDPOINT, $page = 1, $count = 100)
    {
        $url      = $endpiont . 'api/post/list';
        $response = $this->client->request('GET', $url, [
            'http_errors' => false,
            'query'       => [
                'page'     => $page,
                'count'    => $count,
                'tag'      => $tag,
                'category' => $category,
            ],
        ]);
        $contents = $response->getBody()->getContents();
        return $contents;

    }
}
