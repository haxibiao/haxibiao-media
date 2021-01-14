<?php

namespace Haxibiao\Media\Console;

use App\Post;
use App\User;
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
            $postsData = $originResults->data;
            info($postsData);
            // dd($posts);
            //获取分页参数
            $last_page = $originResults->meta->last_page;
            $current_page = $originResults->meta->current_page;
            foreach ($postsData as $postData) {

                $total++;
                DB::beginTransaction();
                try {
                    $video = data_get($postData, 'video');
                    //检查是否已经存在对应的video(只检查video去重，不需要检查post)
                    if (Video::where('hash', $video->hash)->exist()) {
                        continue;
                    }
                    $newVideo = new Video();
                    $newVideo->forceFill($video)->saveDataOnly();

                    //同步对应的post
                    $post = array_except($postData, 'video');

                    $vestUser = User::where('role_id', User::VEST_STATUS)->inRandomOrder()->first();
                    throw_if(empty($vestUser), \Exception::class, "未找到系统马甲号，无法完成同步");

                    $review_id = Post::makeNewReviewId();
                    $review_day = Post::makeNewReviewDay();
                    $postFields = [
                        'user_id' => $vestUser->id,
                        'review_id' => $review_id,
                        'review_day' => $review_day,

                    ];
                    $postFields = array_merge($postFields, $post);
                    $newPost = new Post();
                    $newPost->forceFill([
                        $postFields,
                    ])->saveDataOnly();
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
