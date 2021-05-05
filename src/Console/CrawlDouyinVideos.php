<?php

namespace Haxibiao\Media\Console;

use App\Post;
use App\Spider;
use App\User;
use GuzzleHttp\Client;
use Haxibiao\Media\Jobs\SpiderProcess;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class CrawlDouyinVideos extends Command
{
    /**
     * 抖音接口不可用，这里用的是第三方接口（热榜提供50个视频）
     * 原网页：https://www.jxpie.com/index.html
     */
    protected $signature = 'crawl:dy_videos';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $count = 0;
        $url   = "https://1911958968496216.cn-shanghai.fc.aliyuncs.com/2016-08-15/proxy/python/douyin/4";
        // 获取总页数
        $client   = new Client(['time_out' => 5]);
        $response = $client->request('GET', $url, [
            'http_errors' => false,
        ]);
        $contents = $response->getBody()->getContents();

        throw_if(empty($contents), GQLException::class, '获取内容链接失败!');

        $contents = json_decode($contents, true);
        $videos   = data_get($contents, 'billboard_data');

        foreach ($videos as $video) {

            //随机一个取马甲号用户
            $vestUserIds = User::where('role_id', User::VEST_STATUS)
                ->inRandomOrder()->pluck('id')->toArray();
            $user_id = array_random(array_values($vestUserIds));

            $shareUrl = data_get($video, 'link');
            $title    = data_get($video, 'title');
            $spider   = Spider::has('video')->firstOrNew(['source_url' => $shareUrl]);
            if (isset($spider->id)) {
                $this->info("该视频已爬取过了，跳过该条 ");
                continue;

            }
            $this->info("开始爬取视频 " . $title . $shareUrl);

            $spider->user_id     = $user_id;
            $spider->spider_type = 'videos';
            $spider->saveDataOnly();
            //创建对应的动态
            $post              = \Haxibiao\Content\Post::firstOrNew(['spider_id' => $spider->id]);
            $post->status      = Post::PRIVARY_STATUS;
            $post->user_id     = $user_id;
            $post->description = str_replace(['#在抖音，记录美好生活#', '@抖音小助手', '抖音', 'dou', 'Dou', 'DOU', '抖音助手'], '', $title);

            //触发PostObserver更新快速推荐review_id
            $post->save();
            //将视频归入合集中
            $postIds[$post->id] = ['sort_rank' => data_get($video, 'mix_info.statis.current_episode')];

            //登录
            Auth::login(User::find($user_id));
            try {
                //爬取对应的数据
                dispatch(new SpiderProcess($spider->id));
                $count++;
            } catch (\Exception $ex) {
                $info = $ex->getMessage();
                info("异常信息" . $info);
            }

        }

        $this->info("本次爬取视频 " . $count, "个");

    }
}
