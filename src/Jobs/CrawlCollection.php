<?php

namespace Haxibiao\Media\Jobs;

use GuzzleHttp\Client;
use Haxibiao\Content\Collection;
use Haxibiao\Content\Post;
use Haxibiao\Media\Jobs\MediaProcess;
use Haxibiao\Media\Spider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Storage;

class CrawlCollection implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;
    public $timeout = 6000;
    protected $user, $user_share_link;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user, $user_share_link)
    {
        $this->user            = $user;
        $this->user_share_link = $user_share_link;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->processCrawl($this->user_share_link);
    }

    public function processCrawl($share_link)
    {
        //获取抖音用户id
        $user_id     = self::getDouYinUserId($share_link);
        $hasMore     = true;
        $collections = [];
        $mixIds      = [];
        for ($cursor = 0, $count = 15; $hasMore;) {
            $crawlUrl = sprintf(Spider::COLLECTIONS_URL, $user_id, $cursor, $count);
            //获取用户所有的合集信息
            $collectionData = self::getRequestData($crawlUrl);
            $hasMore        = (bool) data_get($collectionData, 'has_more', 0);
            $cursor         = data_get($collectionData, 'cursor', 0);
            $mixInfos       = data_get($collectionData, 'mix_infos', null);
            if (empty($mixInfos)) {
                continue;
            }
            //未指定爬取合集的情况，爬取该用户下的所有合集
            if (empty($collectionsName)) {
                foreach ($mixInfos as $index => $mixInfo) {
                    // 爬用户TOP3合集，https://pm.haxifang.com/browse/HANJU-22
                    if ($index > 2) {
                        continue;
                    }
                    //创建对应的collection
                    $collection          = self::getMixInfoCollection($mixInfo, $this->user);
                    $mixId               = data_get($mixInfo, 'mix_id');
                    $mixIds[]            = $mixId;
                    $collections[$mixId] = $collection;
                }

            }

        }

        //爬取每个合集下的视频
        foreach ($mixIds as $mixId) {
            $hasMore = true;
            $postIds = [];
            for ($cursor = 0, $count = 15; $hasMore;) {
                $crawlUrl  = sprintf(Spider::VIDEOS_URL, $mixId, $cursor, $count);
                $videoData = self::getRequestData($crawlUrl);
                $hasMore   = (bool) data_get($videoData, 'has_more', 0);
                $cursor    = data_get($videoData, 'cursor', 0);

                $videos = data_get($videoData, 'aweme_list');

                foreach ($videos as $video) {
                    $created             = date('Y-m-d H:i:s', data_get($video, 'create_time'));
                    $shareUrl            = data_get($video, 'share_info.share_url');
                    $spider              = Spider::has('video')->firstOrNew(['source_url' => $shareUrl]);
                    $spider->user_id     = $this->user->id;
                    $spider->spider_type = 'videos';
                    $spider->saveDataOnly();
                    //创建对应的动态
                    $post              = Post::firstOrNew(['spider_id' => $spider->id]);
                    $post->status      = Post::PRIVARY_STATUS;
                    $post->created_at  = $created;
                    $post->user_id     = $this->user->id;
                    $shareTitle        = data_get($video, 'share_info.share_title');
                    $post->description = str_replace(['#在抖音，记录美好生活#', '@抖音小助手', '抖音', 'dou', 'Dou', 'DOU', '抖音助手'], '', $shareTitle);

                    $reviewDay        = $post->created_at->format('Ymd');
                    $reviewId         = Post::makeNewReviewId($reviewDay);
                    $post->review_id  = $reviewId;
                    $post->review_day = $reviewDay;
                    $post->save();
                    //将视频归入合集中
                    $postIds[$post->id] = ['sort_rank' => data_get($video, 'mix_info.statis.current_episode')];

                    // //登录
                    // Auth::login($vestUser);
                    try {
                        //爬取对应的数据
                        dispatch(new MediaProcess($spider->id));
                    } catch (\Exception $ex) {
                        $info = $ex->getMessage();
                        info("异常信息" . $info);
                    }

                }
            }

            //将视频归入合集中
            $collection = $collections[$mixId];
            $collection->posts()->sync($postIds);

            $collection->updateCountPosts();
        }

    }

    //根据mix_info创建Collection
    public static function getMixInfoCollection($mixInfo, $vestUser)
    {
        $name        = data_get($mixInfo, 'mix_name');
        $description = data_get($mixInfo, 'desc');
        $logo        = data_get($mixInfo, 'cover_url.url_list.0');

        $cosPath = 'images/' . uniqid() . '.jpeg';
        Storage::cloud()->put($cosPath, file_get_contents($logo));
        $newImagePath = Storage::cloud()->url($cosPath);
        $collection   = Collection::firstOrCreate(
            ['name' => $name, 'user_id' => $vestUser->id],
            [
                'description' => $description,
                'type'        => 'posts',
                'logo'        => $newImagePath,
                'status'      => Collection::STATUS_ONLINE,
                'json'        => [
                    'mix_info' => $mixInfo,
                ]]
        );

        return $collection;
    }

    //获取抖音用户id
    public static function getDouYinUserId($url = '')
    {

        throw_if(is_null($url), GQLException::class, "主页链接为空");

        $url = Spider::extractURL($url);

        $client   = new Client();
        $response = $client->request('GET', $url, [
            'http_errors'     => false,
            'allow_redirects' => false,
        ]);
        throw_if($response->getStatusCode() == 404, GQLException::class, '您分享的链接不存在,请稍后再试!');

        $referUrl = data_get($response->getHeader('location'), '0', '');
        throw_if(is_null($referUrl), GQLException::class, "获取抖音用户ID失败");

        $start = strripos($referUrl, "/");
        $end   = strpos($referUrl, "?");

        $user_id = substr($referUrl, $start + 1, $end - $start - 1);

        return $user_id;
    }

    /**
     *  获取链接数据
     * $url:需要请求的分页地址
     */

    public static function getRequestData($url = '')
    {
        $client   = new Client();
        $response = $client->request('GET', $url, [
            'http_errors' => false,
        ]);
        throw_if($response->getStatusCode() == 404, GQLException::class, '您分享的链接不存在,请稍后再试!');

        $contents = $response->getBody()->getContents();
        throw_if(empty($contents), GQLException::class, '获取内容链接失败!');

        $contents = json_decode($contents, true);
        return $contents;

    }
}
