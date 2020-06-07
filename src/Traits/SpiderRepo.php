<?php

namespace haxibiao\media\Traits;

use App\Exceptions\UserException;
use GuzzleHttp\Client;
use haxibiao\media\Jobs\MediaProcess;
use haxibiao\media\Spider;
use haxibiao\media\Video;
use Illuminate\Support\Arr;

trait SpiderRepo
{
    public static function resolveDouyinVideo($user, $shareLink)
    {
        $title = Spider::extractTitle($shareLink);
        //提取URL
        $dyUrl = Spider::extractURL($shareLink);

        $isDyUrl = Arr::get(parse_url($dyUrl), 'host') == Spider::DOUYIN_VIDEO_DOMAIN;
        throw_if(!$isDyUrl, UserException::class, '解析失败,请提供有效的抖音URL!');
        throw_if($user->ticket < 1, UserException::class, '分享失败,精力点不足,请补充精力点!');

        //判断是否404
        $client = new Client();
        $client = $client->request('GET', $dyUrl, ['http_errors' => false]);
        throw_if($client->getStatusCode() == 404, UserException::class, '解析失败,URL无法访问！');
        //写入DB
        $spider        = Spider::firstOrNew(['source_url' => $dyUrl]);
        $spiderExisted = isset($spider->id);
        if ($spiderExisted) {
            $spider->count++;
        } else {
            $spider->user_id     = $user->id;
            $spider->spider_type = 'videos';
            $data                = $spider->data;
            $data['title']       = $title;
            $spider->data        = $data;
        }
        $spider->save();

        if ($spiderExisted) {
            if (!is_testing_env()) {
                throw_if($spider->user_id == $user->id, UserException::class, '正在重新采集中,请稍后再看!');
                throw new UserException('该视频链接已被他人分享过了哦!');
            }
        }

        //放入队列，交给media服务
        dispatch(new MediaProcess($spider->id));

        //旧版本自己下载上传cos
        // dispatch(new SpiderProcess($spider->id))->onQueue('spiders');

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
            return str_replace(['#在抖音，记录美好生活#', '@抖音小助手', '抖音', '@DOU+小助手'], '', $match[1][0]);
        }
    }

    /**
     * 用户的爬虫的查询
     */
    public static function querySpiders($user, $type)
    {
        $query = Spider::with('video')->where('user_id', $user->id)
        // ->take($limit)
        // ->skip($offset)
            ->latest('id');
        if (!is_null($type)) {
            $query = $query->where('spider_type', $type);
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
            $video->disk   = 'vod';
            $video->fileid = Arr::get($json, 'vod.FileId');
            $video->path   = $mediaUrl;
            //保存视频截图 && 同步填充信息
            $video->status = empty($coverUrl) ? Video::CDN_VIDEO_STATUS : Video::COVER_VIDEO_STATUS;
            $video->setJsonData('cover', $coverUrl);
            $video->setJsonData('sourceVideoUrl', $mediaUrl);
            $video->setJsonData('duration', Arr::get($data, 'duration', 0));
            $video->setJsonData('width', Arr::get($data, 'width'));
            $video->setJsonData('height', Arr::get($data, 'height'));
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
}