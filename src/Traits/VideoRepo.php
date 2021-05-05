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
    public function autoPublishContentWhenAboutMovie()
    {
        $video = $this;
        $user  = $video->user;
        //剪辑电影的视频
        if ($movie = $video->movie) {

            // 创建用户合集
            $collection = Collection::firstOrNew([
                'name'    => "{$movie->name}的剪辑",
                'type'    => 'post',
                'user_id' => $user->id,
            ]);
            //合集封面
            $collection->logo = $movie->cover_url;
            $collection->save();

            // 创建用户专题
            $category = Category::firstOrNew([
                'name' => "{$movie->name}",
                'type' => 'movie',
            ]);
            // 第一个剪辑同名电影的自动成为专题创建者
            if (!$category->user_id) {
                $category->user_id = $user->id;
            } else {
                //后面剪辑的自动成为专题编辑用户
                $category->addAuthor($user);
            }
            //专题封面
            $category->logo = $movie->cover_url;
            // 默认专题通过审核
            $category->status = Category::STATUS_PUBLIC;
            $category->save();

            // 发布动态
            $post = Post::firstOrNew([
                'user_id'  => $user->id,
                'video_id' => $video->id,
                'movie_id' => $movie->id,
                'status'   => Post::PUBLISH_STATUS,
            ]);
            $post->description   = $video->title; //视频剪辑的配文
            $post->collection_id = $collection->id; //主合集
            $post->category_id   = $category->id; //主专题
            $post->save();

            //自动收入合集
            if ($post->collection_id) {
                $post->addCollections([$post->collection_id]);
            }

            //自动收入专题
            if ($post->category_id) {
                $post->addCategories([$post->category_id]);
            }

            //剪辑的视频，自动生成视频类文章
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

            // 投稿到专题 - SEO目的
            if ($post->category_id) {
                //投稿到电影专题下
                $article->addCategories([$post->category_id]);
                //维护主专题/合集，查询性能优化
                $article->category_id   = $post->category_id;
                $article->collection_id = $post->collection_id;
                $article->save();
            }
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
     * 从vod拿到视频的截图
     * @deprecated
     */
    public function processVod()
    {
        dd('processVod 已重构去哈希云');
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
            Visit::saveVisits($user, $videos, Visit::FAKE_VISITED);
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
     * fileid 获取vod信息至video(兼容答赚老项目也许在用)
     */
    public static function saveByVodFileId($fileId, User $user)
    {
        $vodJson  = Video::getVodJson($fileId);
        $url      = data_get($vodJson, 'basicInfo.sourceVideoUrl');
        $cover    = data_get($vodJson, 'basicInfo.coverUrl');
        $duration = data_get($vodJson, 'basicInfo.duration');
        $height   = data_get($vodJson, 'metaData.height');
        $width    = data_get($vodJson, 'metaData.width');
        $hash     = md5_file($url);

        $video = Video::firstOrNew([
            'fileid' => $fileId,
        ]);
        $video->user_id  = $user->id;
        $video->disk     = 'vod';
        $video->hash     = $hash;
        $video->fileid   = $fileId;
        $video->path     = $url;
        $video->width    = $width;
        $video->height   = $height;
        $video->duration = $duration;
        $video->cover    = $cover;
        $video->json     = json_encode($vodJson);
        $video->save();
        return $video;
    }

    /**
     * 处理哈希云hook
     *
     * @param array $videoArr
     * @return Video
     */
    public static function hook(array $videoArr)
    {
        //media hook 返回整个video对象
        $data = $videoArr;
        $json = Arr::get($data, 'json');
        $hash = Arr::get($data, 'hash');

        //新增2个字段，替代hash做回调用
        $fileid    = Arr::get($data, 'fileid');
        $sharelink = Arr::get($data, 'sharelink');

        $mediaUrl      = Arr::get($data, 'url');
        $vid           = Arr::get($data, 'vid');
        $cover         = Arr::get($data, 'cover');
        $dynamic_cover = Arr::get($data, 'dynamic_cover');

        //主动上传的
        if ($fileid) {
            $video = Video::firstOrNew(['fileid' => $fileid]);
        } else if ($sharelink) {
            //秒粘贴的
            $video = Video::firstOrNew(['sharelink' => $sharelink]);
        }
        //hash是哈希云上传vod处理后才有的
        $video->hash = $hash;

        if (!isset($video->id)) {
            $video->disk = 'vod';

            if (blank($fileid)) {
                //提取fileid
                $fileId = Arr::get($json, 'vod.FileId');
                if (empty($fileId)) {
                    $mediaUrl = Arr::get($json, 'vod.MediaUrl');
                    if ($mediaUrl) {
                        $fileId = Spider::extractFileId($mediaUrl);
                    }
                }
            }
            //保存fileid
            $video->fileid = $fileId;

            $video->path = $mediaUrl;
            //保存视频截图 && 同步填充信息
            $video->status = Video::CDN_VIDEO_STATUS;

            $video->setJsonData('sourceVideoUrl', $mediaUrl);
            $video->setJsonData('duration', Arr::get($data, 'duration', 0));
            $video->setJsonData('width', data_get($json, 'width'));
            $video->setJsonData('height', data_get($json, 'height'));

            // 哈希云统一处理封面 回调回来
            $video->setJsonData('cover', $cover);
            $video->setJsonData('dynamic_cover', $dynamic_cover);

            // 哈希云通过分享链接通知存储vid
            $video->vid = $vid;
            $video->saveQuietly();
        }

    }

    public static function getVodJson($fileid)
    {
        return @file_get_contents(Video::getMediaBaseUri() . 'api/vod/' . $fileid);
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
}
