<?php

namespace Haxibiao\Media\Traits;

use GuzzleHttp\Client;
use Haxibiao\Breeze\Exceptions\GQLException;
use Haxibiao\Breeze\Exceptions\UserException;
use Haxibiao\Breeze\User;
use Haxibiao\Content\Post;
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

        $post = Spider::pasteDouyinVideo($user, $shareLink, $content);
        //维护标签
        if ($post) {
            $post->tagByNames($tagNames);
            $post->saveQuietly();
            return $post->spider;
        } else {
            return null;
        }

    }

    /**
     * 快速魔法粘贴
     *
     * @param User $user
     * @param string $shareLink
     * @param string $content
     * @return Post
     */
    public static function pasteDouyinVideo($user, $shareLink, $content)
    {
        $content = static::extractTitle($content);
        //提取URL
        $dyUrl = static::extractURL($shareLink);
        //截取参数前的链接，防止链接过长
        if ($dyUrl && str_contains($dyUrl, '?')) {
            $end   = strpos($dyUrl, "?");
            $dyUrl = substr($dyUrl, 0, $end);
        }

        // if (Spider::where('source_url', $dyUrl)->exists()) {
        //     throw new GQLException('该视频已被采集，请再换一个！');
        // }

        //粘贴视频的信息
        $pasteVideoInfo = SpiderRepo::getPasteVideoInfo($dyUrl);

        //乐观创建视频
        $video          = Video::firstOrNew(['sharelink' => $dyUrl]);
        $video->title   = $content;
        $video->user_id = $user->id;
        //播放地址+封面 乐观存json,避免path cover字段溢出
        $video->json = $pasteVideoInfo;
        $video->saveQuietly();

        //播放地址靠谱，再创建爬虫和动态...
        if (filter_var(data_get($pasteVideoInfo, 'play_url'), FILTER_VALIDATE_URL)) {

            $title = data_get($pasteVideoInfo, 'title') ?? $content;
            //爬虫
            $spider = Spider::firstOrNew([
                'source_url' => $dyUrl,
            ]);
            $spider->fill([
                'spider_id'   => $video->id,
                'spider_type' => 'videos',
                'user_id'     => $user->id,
                'raw'         => $pasteVideoInfo, //以前的spider raw 格式是抖音json,不过哈希云之外的spider 只有source_url有价值
            ]);
            $spider->setTitle($title);
            $spider->save();

            //动态
            $post = Post::firstOrNew([
                'spider_id' => $spider->id,
            ]);
            $post->video_id = $video->id;
            $post->user_id  = $user->id;
            //乐观发布动态
            $post->status      = Post::PUBLISH_STATUS;
            $post->description = $title;
            $post->save();

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
    public function hookVideo(array $videoArr)
    {
        $video = $this->hookedVideo();
        //爬虫都应该创建了一个video
        if (is_null($video)) {
            return null;
        }

        $video->hook($videoArr);

        //更新爬虫状态
        $this->status = Spider::PROCESSED_STATUS;
        $this->save();
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
     * 粘贴解释
     *
     * @param string $dyUrl
     * @return array
     */
    public static function getPasteVideoInfo($dyUrl)
    {
        $parse_api = Video::getMediaBaseUri() . 'api/spider/paste?source_url=' . $dyUrl;
        if ($result = @file_get_contents($parse_api)) {
            $data = data_get(@json_decode($result, true), 'data');
            return $data;
        }
        return null;
    }

    /**
     * 视频文章+爬虫时代代码
     * @deprecated
     */
    public static function parse($url)
    {
        $hookUrl  = url('api/media/hook');
        $data     = [];
        $client   = new Client();
        $response = $client->request('GET', \Haxibiao\Media\Video::getMediaBaseUri() . 'api/spider/store', [
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

    public static function findByUrl($sharelink)
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

    /**
     * 调用哈希云处理爬虫，等hook回调
     */
    public function process()
    {
        $spider     = $this;
        $source_url = $this->source_url;
        if (!empty($source_url)) {
            // 爬虫回调
            $hookUrl = env('APP_URL') . '/api/media/hook';
            $data    = [];
            $client  = new Client();

            // 提交爬虫
            $api      = \Haxibiao\Media\Video::getMediaBaseUri() . 'api/spider/store';
            $response = $client->request('GET', $api, [
                'http_errors' => false,
                'query'       => [
                    'source_url' => urlencode(trim($source_url)),
                    'hook_url'   => $hookUrl,
                ],
            ]);
            $contents = $response->getBody()->getContents();
            if (!empty($contents)) {
                $contents      = json_decode($contents, true);
                $data          = Arr::get($contents, 'data');
                $spider_status = Arr::get($data, 'status');

                // 同步哈希云爬虫已成功状态
                if ($spider_status == 'PROCESSED_STATUS') {
                    $spider->status = Spider::PROCESSED_STATUS;
                    $spider->saveQuietly();
                }
            }

            $videoArr = Arr::get($data, 'video');
            $status   = Arr::get($videoArr, 'status');

            // 已经被处理过的粘贴，重试的话秒返回hook...
            if ($status == "PROCESSED_STATUS") {
                if (is_array($videoArr)) {
                    $spider->hookVideo($videoArr);
                }
            }
        }
    }
}
