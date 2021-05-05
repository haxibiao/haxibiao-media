<?php

namespace Haxibiao\Media\Traits;

use App\Post;
use GuzzleHttp\Client;
use Haxibiao\Breeze\Exceptions\GQLException;
use Haxibiao\Breeze\Exceptions\UserException;
use Haxibiao\Breeze\User;
use Haxibiao\Media\Jobs\SpiderProcess;
use Haxibiao\Media\Spider;
use Haxibiao\Media\Video;
use Illuminate\Support\Arr;

trait SpiderRepo
{
    public static function resolveDouyinVideo($user, $shareLink, $content = null, $tagNames = [])
    {
        // 通过config来控制接口开关 && 动态配置控制每用户日最大解析数
        throw_if(config('media.spider.enable') === false, UserException::class, '解析失败,功能维护中,请稍后再试!');

        // 解释限制次数问题，统一到哈希云

        //提取URL
        $dyUrl = static::extractURL($shareLink);

        $isDyUrl = in_array(
            Arr::get(parse_url($dyUrl), 'host'),
            Spider::DOUYIN_VIDEO_DOMAINS
        );
        throw_if(!$isDyUrl, UserException::class, '解析失败,请提供有效的抖音URL!');

        //判断是否404(跳过tiktok)
        if (!strpos($dyUrl, 'tiktok.com')) {
            $client = new Client();
            $client = $client->request('GET', $dyUrl, ['http_errors' => false]);
            throw_if($client->getStatusCode() == 404, UserException::class, '解析失败,URL无法访问！');
        }

        $post = Spider::fastProcessDouyinVideo($user, $shareLink, $content);
        // 维护标签
        $post->tagByNames($tagNames);
        $post->saveQuietly();

        return $post->spider;
    }

    /**
     * 快速魔法粘贴
     *
     * @param User $user
     * @param string $shareLink
     * @param string $content
     * @return Post
     */
    public static function fastProcessDouyinVideo($user, $shareLink, $content)
    {
        $content = static::extractTitle($content);
        $title   = static::extractTitle($shareLink);
        //提取URL
        $dyUrl = static::extractURL($shareLink);

        // if (Spider::where('source_url', $dyUrl)->exists()) {
        //     throw new GQLException('该视频已被采集，请再换一个！');
        // }

        //秒粘贴获取video info
        $fastVideoInfo = Spider::getFastDouyinVideoInfo($dyUrl);
        //视频
        $video        = Video::firstOrNew(['sharelink' => $dyUrl]);
        $video->title = $title;
        //秒粘贴尊中sharelink排重，乐观更新fastJson的meta到json
        $video->json = $fastVideoInfo;
        $video->saveQuietly();

        if (filter_var(data_get($fastVideoInfo, 'play_url'), FILTER_VALIDATE_URL)) {
            //爬虫 = vod job
            $spider = Spider::firstOrNew([
                'source_url' => $dyUrl,
            ]);
            $spider->fill([
                'spider_id'   => $video->id,
                'spider_type' => 'videos',
                'user_id'     => $user->id,
                'raw'         => data_get($fastVideoInfo, 'data'),
            ]);
            $spider->save();
            dispatch(new SpiderProcess($spider));

            //动态
            $post = Post::firstOrNew([
                'spider_id' => $spider->id,
            ]);
            $post->video_id = $video->id;
            $post->user_id  = $user->id;
            //秒粘贴可直接秒发布 补刀更新observer自动创建的Post
            $post->status      = Post::PUBLISH_STATUS;
            $post->description = $content ?? $title;
            $post->saveQuietly();

            return $post;
        }
        return null;
    }

    public static function extractFileId($mediaUrl)
    {
        $fileId = substr(preg_split("~/~", $mediaUrl)[4], -19);
        return $fileId;
    }

    public static function extractURL($str)
    {
        throw_if(empty($str), UserException::class, '提取失败,解析文本不能为空!');
        $regex = '@(?i)\b((?:[a-z][\w-]+:(?:/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))@';
        preg_match($regex, $str, $match);
        return $match[0] ?? null;
    }

    public static function extractTitle($str)
    {
        preg_match_all('#(.*?)http.*?#', $str, $match);
        if (isset($match[1][0])) {
            $str = $match[1][0];
        }
        return str_replace(['#在抖音，记录美好生活#', '@抖音小助手', '抖音', '@DOU+小助手', '快手', '#快手创作者服务中心', ' @快手小助手', '#快看'], '', $str);
    }

    /**
     * 用户的爬虫的查询
     *
     * @oldGraphql 标识是否为老版本的GraphQL
     */
    public static function querySpiders($user, $type, $oldGraphql = false)
    {
        $query = Spider::with('video')->where('user_id', $user->id)
        // ->take($limit)
        // ->skip($offset)
            ->latest('id');
        if (!is_null($type)) {
            $query = $query->where('spider_type', $type);
        }

        //旧版本 GraphQL 直接返回 build 将会抛出 Error:expected iterable, but did not find one for field Query.spiders.
        if ($oldGraphql) {
            return $query->get();
        }
        return $query;
    }

    public function replaceTitleBadWord()
    {
        $data  = $this->data;
        $title = Arr::get($data, 'title');
        if (!empty($title)) {
            $title = str_replace(['#在抖音，记录美好生活#', '@抖音小助手', '抖音小助手', '抖音'], '', $title);
        }
        $data['title'] = $title;
        $this->data    = $data;
    }

    /**
     * 内涵云 media hook回调要走这里保存视频信息，发布动态，处理快速排查推荐..
     *
     * @param array $videoArr
     * @return Video
     */
    public function hook(array $videoArr)
    {
        $video = $this->hookedVideo();
        //爬虫都应该创建了一个video
        if (is_null($video)) {
            return null;
        }
        $video->hook($videoArr);

        //更新爬虫状态
        $this->status = Spider::PROCESSED_STATUS;
        //Observer处理media hook回调剩余的更新维护逻辑
        $this->save();

        //FIXME: 发布动态的奖励逻辑也不在这里处理...

        //触发奖励逻辑也不在这里处理
        // $reward = Spider::SPIDER_GOLD_REWARD;

        // Gold::makeIncome($user, $reward, '分享视频奖励');
        //扣除精力逻辑也不在这里处理
        // if ($user->ticket > 0) {
        //     $user->decrement('ticket');
        // }

        return $video;
    }

    public function setTitle($title)
    {
        $data          = Arr::get($this, 'data', []);
        $data['title'] = $title;
        $this->data    = $data;

        return $this;
    }

    /**
     * 新版本秒解释，未配置media/hook回调
     *
     * @param [type] $dyUrl
     * @return array
     */
    public static function getFastDouyinVideoInfo($dyUrl): array
    {
        $result = @file_get_contents(Video::getMediaBaseUri() . 'api/v1/spider/parse?share_link=' . $dyUrl);
        $data   = data_get(json_decode($result, true), 'raw');
        return [
            'play_url'      => data_get($data, 'video.play_url'),
            'title'         => data_get($data, 'video.info.0.desc'),
            'cover'         => data_get($data, 'raw.item_list.0.video.origin_cover.url_list.0'),
            'width'         => data_get($data, 'raw.item_list.0.video.width'),
            'height'        => data_get($data, 'raw.item_list.0.video.height'),
            'duration'      => ceil(data_get($data, 'raw.item_list.0.duration') / 1000), //参考createPost逻辑
            'dynamic_cover' => data_get($data, 'raw.item_list.0.video.dynamic_cover.url_list.0'),
            'share_link'    => $dyUrl, //参考createPost逻辑
        ];
    }

    /**
     * 哈希云队列解释,media/hook 回调
     */
    public static function parse($url)
    {
        $hookUrl  = url('api/media/hook');
        $data     = [];
        $client   = new Client();
        $response = $client->request('GET', \Haxibiao\Media\Video::getMediaBaseUri() . 'api/v1/spider/store', [
            'http_errors' => false,
            'query'       => [
                'source_url' => trim($url),
                'hook_url'   => $hookUrl,
            ],
        ]);
        throw_if($response->getStatusCode() == 404, GQLException::class, '您分享的链接不存在,请稍后再试!');
        $contents = $response->getBody()->getContents();
        if (!empty($contents)) {
            $contents = json_decode($contents, true);
            $data     = Arr::get($contents, 'data');
        }

        return $data;
    }

    public static function findByUrl($sharelink): Spider
    {
        return Spider::where('source_url', $sharelink)->first();
    }

    public function hookedVideo(): Video
    {
        if ($video = $this->video) {
            return $video;
        }
        $video = Video::firstOrNew(['sharelink' => $this->source_url]);
        $video->saveQuietly();
        return $video;
    }
}
