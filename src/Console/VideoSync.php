<?php

namespace Haxibiao\Media\Console;

use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VideoSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'video:sync {--tag=: 视频标签} {--category=: 视频分类}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '按分类同步视频数据';
    protected const POST_URL = 'http://media.haxibiao.com/api/post/list';

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

        $tag = $this->option('tag');
        $category = $this->option('category');

        $success = 0;
        $fail = 0;
        $total = 0;

        for ($last_page = 1, $current_page = 1; $last_page <= $current_page;) {
            //提交或者重试爬虫
            $response = self::getUrlResponse($tag, $category);
            $originResults = json_decode($response);
            $posts = $originResults->data;
            //获取分页参数
            $last_page = $originResults->meta->last_page;
            $current_page = $originResults->meta->current_page;
            foreach ($posts as $post) {
                //todo 分页插入

                $total++;
                DB::beginTransaction();
                try {
                    DB::commit();
                    $success++;
                    $this->info('成功');
                } catch (\Exception $ex) {
                    dd($ex);
                    DB::rollback();
                    $fail++;
                    $this->error('导入失败');
                }
            }
        }

        $this->info('共检索出' . $total . '部电影,成功导入：' . $success . '部,失败：' . $fail . '部');
    }

    public function getUrlResponse($tag, $category, $url = self::POST_URL, $page = 1, $count = 100)
    {
        $response = $this->client->request('GET', $url, [
            'http_errors' => false,
            'query' => [
                'page' => $page,
                'count' => $count,
                'tag' => $tag,
                'category' => $category,
            ],
        ]);
        $contents = $response->getBody()->getContents();
        return $contents;

    }
}
