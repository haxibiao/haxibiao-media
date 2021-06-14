<?php

namespace Haxibiao\Media\Traits;

use App\Question;
use App\User;
use App\Video;
use App\Visit;
use Haxibiao\Breeze\Exceptions\UserException;
use Haxibiao\Content\Article;
use Haxibiao\Content\Category;
use Haxibiao\Content\Collection;
use Haxibiao\Content\Post;
use Haxibiao\Media\Spider;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use TencentCloud\Common\Credential;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Vod\V20180717\Models\PushUrlCacheRequest;
use TencentCloud\Vod\V20180717\VodClient;

trait VideoRepo
{

    /**
     * 影片解说和剪辑自动关联更新专题
     */
    public function autoHookMovieCategory($post, $movie)
    {
        $user = $this->user;
        // 创建用户专题
        $category = Category::firstOrNew([
            'name' => "{$movie->name}",
            'type' => 'movie',
        ]);
        // 第一个剪辑同名电影的自动成为专题创建者
        if (!$category->user_id) {
            $category->user_id = $user->id;
        } else {
            //后面剪辑的自动成为专题编辑成员
            $category->addAuthor($user);
        }

        // 专题封面
        $category->logo = $movie->cover_url;
        // 默认专题通过审核
        $category->status = Category::STATUS_PUBLISH;
        $category->save();

        // 自动收入专题
        if ($post->category_id) {
            $post->addCategories([$post->category_id]);
        }
        $post->category_id = $category->id; //主专题
        $post->saveQuietly();

        return $category;
    }

    /**
     * 影片解说和剪辑自动收入用户合集
     */
    public function autoHookMovieCollection($post, $movie)
    {
        $user = $this->user;
        // 创建用户下影片的剪辑合集
        $collection = Collection::firstOrNew([
            'name'    => "{$movie->name}的剪辑",
            'type'    => 'post',
            'user_id' => $user->id,
        ]);
        //合集封面
        $collection->logo = $movie->cover_url;
        $collection->save();

        //自动收入合集
        if ($post->collection_id) {
            $post->addCollections([$post->collection_id]);
        }
        $post->collection_id = $collection->id; //主合集
        $post->saveQuietly();
        return $collection;
    }

    /**
     * SEO目的：剪辑的视频动态，自动发布文章并投稿
     */
    public function autoPublishPostLinkedArticle($post, $collection, $category)
    {
        $article = Article::firstOrNew([
            'video_id' => $post->video_id,
            'type'     => 'video',
            'movie_id' => $post->movie_id,
        ]);
        $article->title   = $post->description; //标题来自剪辑，配文字数都不多
        $article->body    = $post->description;
        $article->user_id = $post->user_id;
        //直接发布，投稿成功
        $article->status = Article::STATUS_ONLINE;
        $article->submit = Article::SUBMITTED_SUBMIT;
        $article->save();

        // 投稿到专题
        if ($category) {
            //投稿到电影专题下
            $article->addCategories([$category->id]);
            //主专题
            $article->update(['category_id' => $category->id]);
        }
        if ($collection) {
            //主合集
            $article->update(['collection_id' => $collection->id]);
        }
    }

    /**
     * 剪辑电影产生的视频,自动关联内容系统
     */
    public function autoHookContentWhenClipedMovie()
    {
        $video = $this;
        $user  = $video->user;
        // 剪辑的才有可靠的movie_id值关联到影片
        if ($movie = $video->movie) {
            // 发布动态
            $post = Post::firstOrNew([
                'user_id'  => $user->id,
                'video_id' => $video->id,
                'movie_id' => $movie->id,
                'status'   => Post::PUBLISH_STATUS,
            ]);
            $post->description = $video->title; //视频剪辑的配文
            $post->save();

            //自动收入合集
            $collection = $video->autoHookMovieCollection($post, $movie);
            //自动收入专题
            $category = $video->autoHookMovieCategory($post, $movie);

            //自动生成视频类文章
            $video->autoPublishPostLinkedArticle($post, $collection, $category);
        }
    }

    public function fillForJs()
    {
        $video        = $this;
        $video->url   = $video->url;
        $video->cover = $video->cover; //返回full uri

        //兼容旧接口
        $video->video_id  = $this->id;
        $video->video_url = $this->url;
        $video->image_url = $this->cover;

        return $video;
    }

    public function getPath()
    {
        //TODO: save this->extension, 但是目前基本都是mp4格式
        $extension = 'mp4';
        return '/storage/video/' . $this->id . '.' . $extension;
    }

    /**
     * 旧的本项目上传视频文件，目前不支持，请前端都用云里的vod sdk方式
     * @deprecated
     */
    public function saveFile(UploadedFile $file)
    {
        throw new UserException("请升级版本用vod上传视频");
    }

    public function saveWidthHeight($path)
    {
        $image  = getimagesize($path);
        $width  = $image["0"]; ////获取图片的宽
        $height = $image["1"]; ///获取图片的高

        $this->setJsonData('width', $width);
        $this->setJsonData('height', $height);
        $this->save();
    }

    public function pushUrlCacheRequest($url)
    {
        //VOD预热
        $cred        = new Credential(env('VOD_SECRET_ID'), env('VOD_SECRET_KEY'));
        $httpProfile = new HttpProfile();
        $httpProfile->setEndpoint("vod.tencentcloudapi.com");

        $clientProfile = new ClientProfile();
        $clientProfile->setHttpProfile($httpProfile);

        $client = new VodClient($cred, "ap-guangzhou", $clientProfile);
        $req    = new PushUrlCacheRequest();
        $params = '{"Urls":["' . $url . '"]}';

        $req->fromJsonString($params);
        return $client->PushUrlCache($req);
    }

    /**
     * @deprecated 答题废弃的视频刷接口，新版本gql需要用新的FastRecommend
     */
    public static function getVideos($user, $type, $limit = 10, $offset = 0)
    {
        $hasUser = !is_null($user);
        //10个中会有2个广告视频 5个有1个广告
        $limit = $limit >= 10 ? 8 : 4;

        $qb = Question::select('video_id')->has('video')->publish()->orderByDesc('rank');

        if ($hasUser) {
            $qb = $qb->where('user_id', '!=', $user->id);

            //排除浏览过的视频
            $visitVideoIds = Visit::ofType('videos')->ofUserId($user->id)->get()->pluck('visited_id');
            if (!is_null($visitVideoIds)) {
                $qb = $qb->whereNotIn('video_id', $visitVideoIds);
            }
        }
        $qb = $qb->take($limit);
        //游客浏览翻页
        if (!$hasUser) {
            //访客第一页随机略过几个视频
            $offset = $offset == 0 ? mt_rand(0, 50) : $offset;
            $qb     = $qb->skip($offset);
        }
        $videoIds = $qb->get();

        $mixVideos = [];
        $videos    = Video::with('question')->whereIn('id', $videoIds->pluck('video_id'))->get();
        $index     = 0;
        foreach ($videos as $video) {
            $index++;
            $mixVideos[] = $video;
            if ($index % 4 == 0) {
                //每隔4个插入一个广告视频
                $adVideo              = clone $video;
                $adVideo->id          = random_str(7);
                $adVideo->is_ad_video = true;
                $mixVideos[]          = $adVideo;
            }
        }

        //暂时保存假的视频浏览记录
        if ($hasUser) {
            // Visit::saveVisits($user, $videos, Visit::FAKE_VISITED);
        }

        return $mixVideos;
    }

    /**
     * @deprecated 原答赚答妹保存UploadFile视频
     */
    public static function saveVideoFile(UploadedFile $videoFile, array $inputs, $user)
    {
        throw new UserException("请升级版本用vod上传视频");
    }

    /**
     * 处理哈希云hook同步视频信息
     *
     * @param array $videoArr 视频在哈希云的数据
     * @return Video
     */
    public function hook(array $videoArr)
    {
        $video    = $this;
        $data     = $videoArr;
        $json     = data_get($data, 'json');
        $hash     = data_get($data, 'hash');
        $mediaUrl = data_get($data, 'url');
        $cover    = data_get($data, 'cover');

        //新增字段
        $fileid    = data_get($data, 'fileid');
        $sharelink = data_get($data, 'sharelink');
        $vid       = data_get($data, 'vid');
        // $dynamic_cover = data_get($data, 'dynamic_cover');

        //主动上传的
        //提取fileid
        if (blank($fileid)) {
            $fileId = data_get($json, 'vod.FileId');
            if (empty($fileId)) {
                $fileId = Spider::extractFileId($mediaUrl);
            }
        }
        if ($fileid) {
            $video->fileid = $fileid;
        }
        if ($sharelink) {
            //粘贴的
            $video->sharelink = $sharelink;
        }

        $video->hash = $hash;
        $video->disk = 'vod';
        // 哈希云通过分享链接通知存储vid
        $video->vid = $vid;

        $video->fileid = $fileid;
        // 播放地址
        $video->path = $mediaUrl;
        // 保存vod视频截图
        if ($cover) {
            $video->cover = $cover;
        }
        $video->json = $json;
        // 同步哈希云视频视频状态
        $video->status = data_get($videoArr, 'status') == 'PROCESSED_STATUS' ? Video::COVER_VIDEO_STATUS : Video::CDN_VIDEO_STATUS;
        $video->saveQuietly();
    }

    /**
     * 获取哈希云的video信息
     *
     * @param string $fileid
     * @return array
     */
    public static function getCloudVideoInfo($fileid)
    {
        $json = @file_get_contents(Video::getMediaBaseUri() . 'api/video/info/' . $fileid);
        $res  = @json_decode($json, true) ?? [];
        return data_get($res, 'data');
    }

    /**
     * 根据fileid返回视频
     *
     * @param string $fileid
     * @return Video
     */
    public static function findByFileId($fileid)
    {
        return Video::where('fileid', $fileid)->first();
    }

    /**
     * nova上传视频
     * @deprecated
     */
    public static function uploadNovaVod($file)
    {
        dd('需要Nova上传视频到vod的，联系 ivan@haxibiao.com 开发nova-tools前端集成vod sdk获取哈希云的vod token');
    }

    /**
     * 上传视频处理到vod
     */
    public function processVod()
    {
        $video = $this;

        //处理 video 的 vod 信息和封面，并hook回来
        $hookUrl = url('api/video/hook');
        $client  = new \GuzzleHttp\Client();

        //提交 哈希云处理vod信息来hook结果
        $apiPath  = 'api/video/store';
        $api      = \Haxibiao\Media\Video::getMediaBaseUri() . $apiPath;
        $response = $client->request('GET', $api, [
            'http_errors' => false,
            'query'       => [
                'fileid'   => urlencode(trim($video->fileid)), //上传视频必须有
                'hook_url' => $hookUrl,
            ],
        ]);

        $response->getBody()->getContents();
        //等hook
    }

}
