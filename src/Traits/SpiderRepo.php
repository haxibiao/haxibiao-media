<?php

namespace Haxibiao\Media\Traits;

use App\Post;
use GuzzleHttp\Client;
use Haxibiao\Breeze\Exceptions\GQLException;
use Haxibiao\Breeze\Exceptions\UserException;
use Haxibiao\Breeze\User;
use Haxibiao\Helpers\utils\QcloudUtils;
use Haxibiao\Media\Jobs\PullUploadVideo;
use Haxibiao\Media\Spider;
use Haxibiao\Media\Video;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

trait SpiderRepo
{
    public static function resolveDouyinVideo($user, $shareLink, $content = null, $tagNames = [])
    {
        // 通过config来控制接口开关 && 动态配置控制每用户日最大解析数
        throw_if(config('media.spider.enable') === false, UserException::class, '解析失败,功能维护中,请稍后再试!');

        // 解释限制次数问题，统一到哈希云

        $title = static::extractTitle($shareLink);
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

    public static function getFastDouyinVideoInfo($dyUrl)
    {
        $url      = sprintf('http://gz0%u.haxibiao.com/simple-spider/parse.php?url=%s', mt_rand(12, 18), $dyUrl);
        $data     = json_decode(file_get_contents($url), true)['data'];
        $videoUrl = $data['video']['play_url'];
        $title    = $data['video']['info']['0']['desc'];
        return [$title, $videoUrl, $data];
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
        if (Spider::where('source_url', $dyUrl)->exists()) {
            throw new GQLException('该视频已被采集，请再换一个！');
        }
        //秒粘贴获取video info
        list($title, $videoUrl, $data) = Spider::getFastDouyinVideoInfo($dyUrl);
        //视频
        $video       = Video::create(['disk' => 'vod', 'title' => $title]);
        $video->json = ['douyin' => [
            'play_url' => $videoUrl,
        ]];
        $video->saveQuietly();
        if (filter_var($videoUrl, FILTER_VALIDATE_URL)) {
            //爬虫 = vod job
            $spider = new Spider([
                'spider_id'   => $video->id,
                'spider_type' => 'videos',
                'user_id'     => $user->id,
                'raw'         => $data,
                'source_url'  => $dyUrl,
            ]);
            $spider->saveQuietly();

            //动态 - 秒粘贴可行，直接秒发布
            $postData = [
                'user_id'   => $user->id,
                'video_id'  => $video->id,
                'spider_id' => $spider->id,
                'status'    => Post::PUBLISH_STATUS,
            ];
            $post              = Post::firstOrNew($postData);
            $post->description = $content ?? $title;
            $post->saveQuietly();

            //FIXME: 重构这个jobs到vod的专属架构位置 哈希云的media
            dispatch(new PullUploadVideo($video));
            return $post;
        }
        return null;
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
     * media hook回调要走这里保存视频信息，发布动态，处理快速排查推荐..
     */
    public function saveVideo($data)
    {
        $hash     = Arr::get($data, 'hash');
        $json     = Arr::get($data, 'json');
        $mediaUrl = Arr::get($data, 'url');
        $coverUrl = Arr::get($data, 'cover');
        $video    = Video::firstOrNew(['hash' => $hash]);

        if (!isset($video->id) || ($video->disk == 'tj')) {
            $video->user_id = $this->user_id;
            //更改VOD地址
            $video->disk = 'vod';

            //获取fileId
            $fileId = Arr::get($json, 'vod.FileId');
            if (empty($fileId)) {
                $mediaUrl = Arr::get($json, 'vod.MediaUrl');
                if ($mediaUrl) {
                    $fileId = substr(preg_split("~/~", $mediaUrl)[4], -19);
                }
            }

            //兼容答赚、工厂等项目
            if (in_array(env("APP_NAME"), ["datizhuanqian", "damei", "yyjieyou", "ablm"])) {
                $video->fileid = $fileId;
            } else {
                $video->qcvod_fileid = $fileId;
            }

            $video->path = $mediaUrl;
            //保存视频截图 && 同步填充信息
            $video->status = empty($coverUrl) ? Video::CDN_VIDEO_STATUS : Video::COVER_VIDEO_STATUS;
            $video->setJsonData('cover', $coverUrl);
            $video->setJsonData('sourceVideoUrl', $mediaUrl);
            $video->setJsonData('duration', Arr::get($data, 'duration', 0));
            $videoInfo = QcloudUtils::getVideoInfo($video->fileid ?? $video->qcvod_fileid);
            $video->setJsonData('width', data_get($videoInfo, 'metaData.width'));
            $video->setJsonData('height', data_get($videoInfo, 'metaData.height'));

            $douyinDynamicCover = data_get($this, 'data.raw.item_list.0.video.dynamic_cover.url_list.0');
            if ($douyinDynamicCover) {
                $stream = @file_get_contents($douyinDynamicCover);
                if ($stream) {
                    $dynamicCoverPath = 'images/' . generate_uuid('webp');
                    $result           = Storage::cloud()->put($dynamicCoverPath, $stream);
                    if ($result) {
                        $video->setJsonData('dynamic_cover', cdnurl($dynamicCoverPath));
                    }
                }
            }
            // 保存vid信息
            $vid = data_get($this, 'data.raw.item_list.0.video.vid');
            if ($vid && Schema::hasColumn('videos', 'vid')) {
                $video->vid = $vid;
            }

            $video->save();
        }

        //FIXME: 更新爬虫和视频关系（crawlable?）
        $reward            = Spider::SPIDER_GOLD_REWARD;
        $this->spider_type = 'videos';
        $this->spider_id   = $video->id;
        $this->status      = Spider::PROCESSED_STATUS;
        $this->save();

        //FIXME: 发布成动态这个需要 haxibiao-content的 post部分逻辑observer spider...
        // $this->savePost();

        //FIXME: content系统部分，发布成功动态的observer里奖励，这里只负责处理media相关业务

        // $user = $this->user;
        // if (!is_null($user)) {
        //     //触发奖励
        //     Gold::makeIncome($user, $reward, '分享视频奖励');
        //     //扣除精力
        //     if ($user->ticket > 0) {
        //         $user->decrement('ticket');
        //     }
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

    public static function getVideoByDyShareLink($shareLink)
    {
        try {
            $shareLink    = static::extractURL($shareLink);
            $url          = sprintf('http://gz0%u.haxibiao.com/simple-spider/parse.php?url=%s', mt_rand(12, 18), $shareLink);
            $data         = data_get(json_decode(@file_get_contents($url), true), 'data');
            $cover        = data_get($data, 'raw.item_list.0.video.origin_cover.url_list.0');
            $width        = data_get($data, 'raw.item_list.0.video.width');
            $height       = data_get($data, 'raw.item_list.0.video.height');
            $duration     = data_get($data, 'raw.item_list.0.duration');
            $dynamicCover = data_get($data, 'raw.item_list.0.video.dynamic_cover.url_list.0');
            $play_url     = data_get($data, 'video.play_url');
            $title        = data_get($data, 'video.info.0.desc');

            $hash  = hash_file('md5', $play_url);
            $video = \App\Video::firstOrNew(['hash' => $hash]);
            if (!isset($video->id)) {
                $video->setJsonData('cover', $cover);
                $video->setJsonData('sourceVideoUrl', $play_url);
                $video->setJsonData('duration', $duration);
                $video->setJsonData('width', $width);
                $video->setJsonData('height', $height);
                $video->setJsonData('dynamic_cover', $dynamicCover);
                $videoData = [
                    'title'    => $title,
                    'path'     => $play_url,
                    'disk'     => 'tj',
                    'duration' => $duration,
                ];
                $video->fill($videoData);
                $video->saveDataOnly();
            }
            return $video;
        } catch (\Exception $e) {
            return null;
        }
    }
}
