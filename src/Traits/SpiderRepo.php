<?php

namespace haxibiao\media\Traits;

use App\Exceptions\UserException;
use App\Gold;
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
            throw_if($spider->user_id == $user->id, UserException::class, '正在重新采集中,请稍后再看!');
            throw new UserException('该视频链接已被他人分享过了哦!');
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

    public static function getSpiders($user, $type, $limit = 0, $offset = 0)
    {
        $query = Spider::with('video')->where('user_id', $user->id)
            ->take($limit)
            ->skip($offset)
            ->latest('id');
        if (!is_null($type)) {
            $query = $query->where('spider_type', $type);
        }

        return $query->get();
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

        //更新视频关系
        $reward            = Spider::SPIDER_GOLD_REWARD;
        $this->spider_type = 'videos';
        $this->spider_id   = $video->id;
        $this->status      = Spider::PROCESSED_STATUS;
        $this->save();

        //发布成动态
        // $spider = $event->spider;
        // $data   = $spider->data;
        // $post   = Post::firstOrNew(['video_id' => $spider->spider_id]);

        // //数据不存在
        // if (!isset($post->id)) {
        //     $post->user_id    = $spider->user_id;
        //     $post->content    = Arr::get($data, 'title', '');
        //     $post->status     = Post::PUBLISH_STATUS;
        //     $post->created_at = now();
        //     $post->updated_at = $spider->updated_at;
        //     $post->save();

        //     //FIXME: 同步肖新明新年写的优化的视频动态推荐算法，随机，按天指针...
        // }

        $user = $this->user;
        if (!is_null($user)) {
            //触发奖励
            Gold::makeIncome($user, $reward, '分享视频奖励');
            //扣除精力
            if ($user->ticket > 0) {
                $user->decrement('ticket');
            }
        }

        return $video;
    }
}
