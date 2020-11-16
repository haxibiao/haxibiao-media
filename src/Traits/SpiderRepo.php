<?php

namespace Haxibiao\Media\Traits;

use App\Exceptions\UserException;
use GuzzleHttp\Client;
use Haxibiao\Helpers\QcloudUtils;
use Haxibiao\Media\Jobs\MediaProcess;
use Haxibiao\Media\Spider;
use Haxibiao\Media\Video;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

trait SpiderRepo
{
    public static function resolveDouyinVideo($user, $shareLink)
    {
        // 通过config来控制接口开关 && 动态配置控制每用户日最大解析数
        throw_if(config('media.spider.enable') === false, UserException::class, '解析失败,功能维护中,请稍后再试!');
        if (!in_array(config('app.name'), [
            'yinxiangshipin', 'ainicheng', 'ablm',
            'youjianqi', 'nashipin', 'dongdianhai',
            'jinlinle', 'damei', 'haxibiao','dongwaimao'
        ])) {
            $limitCount = config('media.spider.user_daily_spider_parse_limit_count');
            //-1不限制次数
            if ($limitCount >= 0) {
                $isLimited  = $user->spiders()->today()->count() >= $limitCount;
                throw_if($isLimited, UserException::class, '解析失败,今日分享已达上限,请明日再试哦!');
            }
        }

        $title = static::extractTitle($shareLink);
        //提取URL
        $dyUrl = static::extractURL($shareLink);

        $isDyUrl = in_array(
            Arr::get(parse_url($dyUrl), 'host'),
            Spider::DOUYIN_VIDEO_DOMAINS
        );
        throw_if(!$isDyUrl, UserException::class, '解析失败,请提供有效的抖音URL!');
        if (!in_array(config('app.name'), ['yinxiangshipin', 'ainicheng', 'dongwaimao', 'ablm', 'nashipin', 'caohan','dongwaimao'])) {
            throw_if($user->ticket < 1, UserException::class, '分享失败,精力点不足,请补充精力点!');
        }

        //判断是否404(跳过tiktok)
        if(!strpos($dyUrl, 'tiktok.com')){
            $client = new Client();
            $client = $client->request('GET', $dyUrl, ['http_errors' => false]);
            throw_if($client->getStatusCode() == 404, UserException::class, '解析失败,URL无法访问！');
        }

        //写入DB
        $spider        = static::firstOrNew(['source_url' => $dyUrl]);
        $spiderExisted = isset($spider->id);
        $isSelf        = $spider->user_id == $user->id;
        if ($spiderExisted && !$isSelf) {
            throw_if(!is_testing_env(), UserException::class, '该视频链接已被他人分享过了哦!');
        }

        if ($spiderExisted) {
            $spider->count++; //粘贴次数
            $spider->status = Spider::WATING_STATUS; //重试进入队列
        } else {
            $spider->user_id     = $user->id;
            $spider->spider_type = 'videos';
            $spider->setTitle($title);
        }
        $spider->save();

        //放入队列，交给media服务
        dispatch(new MediaProcess($spider->id));

        if ($spiderExisted && $isSelf) {
            throw_if(!is_testing_env(), UserException::class, '正在重新采集中,请稍后再看!');
        }

        return $spider;
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
            return str_replace(['#在抖音，记录美好生活#', '@抖音小助手', '抖音', '@DOU+小助手','快手','#快手创作者服务中心',' @快手小助手','#快看'], '', $match[1][0]);
        }
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
        if (!isset($video->id)) {
            $video->user_id = $this->user_id;
            //更改VOD地址
            $video->disk = 'vod';
            if (in_array(env("APP_NAME"), ["datizhuanqian", "damei", "yyjieyou", "ablm"])) {
                $video->fileid = Arr::get($json, 'vod.FileId');
            } else {
                $fileId = Arr::get($json, 'vod.FileId');
                if($fileId){
                    $video->qcvod_fileid = $fileId;
                }else{
                    $mediaUrl = Arr::get($json, 'vod.MediaUrl');
                    if($mediaUrl){
                        $video->qcvod_fileid = substr(preg_split("~/~",$mediaUrl)[4],-19);
                    }
                }
                
            }
            $video->path = $mediaUrl;
            //保存视频截图 && 同步填充信息
            $video->status = empty($coverUrl) ? Video::CDN_VIDEO_STATUS : Video::COVER_VIDEO_STATUS;
            $video->setJsonData('cover', $coverUrl);
            $video->setJsonData('sourceVideoUrl', $mediaUrl);
            $video->setJsonData('duration', Arr::get($data, 'duration', 0));
            $videoInfo = QcloudUtils::getVideoInfo(intval(Arr::get($json, 'vod.FileId')));
            $video->setJsonData('width', data_get($videoInfo, 'metaData.width'));
            $video->setJsonData('height', data_get($videoInfo, 'metaData.height'));

            $douyinDynamicCover = data_get($this, 'data.raw.item_list.0.video.dynamic_cover.url_list.0');
            if ($douyinDynamicCover) {
                $stream = @file_get_contents($douyinDynamicCover);
                if ($stream) {
                    $dynamicCoverPath = 'images/' . genrate_uuid('webp');
                    $result           = Storage::cloud()->put($dynamicCoverPath, $stream);
                    if ($result) {
                        $video->setJsonData('dynamic_cover', Storage::cloud()->url($dynamicCoverPath));
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

    // public function savePost()
    // {
    //     $spider = $this;
    //     $data   = $spider->data;
    //     $post   = Post::firstOrNew(['video_id' => $spider->spider_id]);

    //     //创建动态..
    //     if (!isset($post->id)) {
    //         $post->user_id    = $spider->user_id;
    //         $post->content    = Arr::get($data, 'title', '');
    //         $post->status     = Post::PUBLISH_STATUS; //发布动态
    //         $post->created_at = now();
    //         $post->updated_at = $spider->updated_at;
    //         // $post->review_id  = Post::makeNewReviewId(); //定时发布时决定
    //         // $post->review_day = Post::makeNewReviewDay();
    //         $post->save();

    //         //FIXME: 这个逻辑要放到 content 系统里，PostObserver updated ...
    //         //超过100个动态或者1个小时前,自动发布.
    //         // $canPublished = Post::where('review_day', 0)
    //         //     ->where('created_at', '<=', now()->subHour())->exists()
    //         // || Post::where('review_day', 0)->count() >= 100;

    //         // if ($canPublished) {
    //         //     dispatch_now(new PublishNewPosts);
    //         // }
    //     }

    // }

    public function setTitle($title)
    {
        $data          = Arr::get($this, 'data', []);
        $data['title'] = $title;
        $this->data    = $data;

        return $this;
    }
}
