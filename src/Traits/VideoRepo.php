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
use Haxibiao\Helpers\utils\QcloudUtils;
use Haxibiao\Helpers\utils\VodUtils;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use TencentCloud\Common\Credential;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Vod\V20180717\Models\PushUrlCacheRequest;
use TencentCloud\Vod\V20180717\VodClient;
use Vod\Model\VodUploadRequest;

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
     *
     * @param UploadedFile $file
     * @return void
     */
    public function saveFile(UploadedFile $file)
    {
        throw new UserException("请升级版本用vod上传视频");

        // $this->user_id = getUserId();
        // $this->save(); //拿到video->id

        // $cosPath     = 'video/' . $this->id . '.mp4';
        // $this->path  = $cosPath;
        // $this->hash  = md5_file($file->path());
        // $this->title = $file->getClientOriginalName();
        // $this->save();

        // try {
        //     //本地存一份用于截图
        //     $file->storeAs(
        //         'video', $this->id . '.mp4'
        //     );
        //     $this->disk = 'local'; //先标记为成功保存到本地
        //     $this->save();

        //     //同步上传到cos
        //     $cosDisk = Storage::cloud();
        //     $cosDisk->put($cosPath, Storage::disk('public')->get('video/' . $this->id . '.mp4'));
        //     $this->disk = 'cos';
        //     $this->save();

        //     // dispatch((new MakeVideoCovers($this)))->delay(now()->addMinute(1));
        //     return true;

        // } catch (\Exception $ex) {
        //     Log::error("video save exception" . $ex->getMessage());
        // }
        // return false;
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
     */
    public function processVod()
    {
        //呼叫vod截图任务流
        $videoInfo = QcloudUtils::processVodFile($this->qcvod_fileid);

        //获取截图结果
        sleep(15);
        $videoInfo      = QcloudUtils::getVideoInfo($this->qcvod_fileid);
        $coverUrl       = Arr::get($videoInfo, 'basicInfo.coverUrl');
        $sourceVideoUrl = Arr::get($videoInfo, 'basicInfo.sourceVideoUrl');
        if (is_null($coverUrl)) {
            sleep(15);
            $videoInfo = QcloudUtils::getVideoInfo($this->qcvod_fileid);
            $coverUrl  = Arr::get($videoInfo, 'basicInfo.coverUrl');
        }
        $this->duration = data_get($videoInfo, 'basicInfo.duration', 0);
        $this->cover    = $coverUrl;
        $this->path     = $sourceVideoUrl;
        $this->hash     = hash_file('md5', $sourceVideoUrl);

        //TODO::这里重复给值，可能需要重构
        $this->setJsonData('cover', $coverUrl);
        $this->setJsonData('duration', data_get($videoInfo, 'basicInfo.duration', 0));
        $this->setJsonData('width', data_get($videoInfo, 'metaData.width'));
        $this->setJsonData('height', data_get($videoInfo, 'metaData.height'));
        $this->disk   = "vod";
        $this->status = Video::TRANSCODE_STATUS;
        $this->save();
        //触发截图操作
        // MakeVideoCovers::dispatchNow($this);
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

        // $publicStorage = Storage::disk('public');

        // if (!$publicStorage->exists('videos')) {
        //     $publicStorage->makeDirectory('videos');
        // }

        // $videoName = $videoFile->getFilename();
        // //example video/SDV_QSVV.mp4
        // $videoPath = 'videos/' . $videoName . '.' . $videoFile->getClientOriginalExtension();
        // $hash      = hash_file('md5', $videoFile->getRealPath());

        // $video = Video::firstOrNew(['hash' => $hash]);
        // if (!isset($video->id)) {
        //     $isMoveSuccess = $publicStorage->put($videoPath, $videoFile->get());
        //     if ($isMoveSuccess) {
        //         $video->fill([
        //             'user_id'  => $user->id ?? null,
        //             'path'     => $videoPath,
        //             'disk'     => 'damei',
        //             'filename' => $inputs['videoName'] ?? null,
        //             'app'      => $inputs['app'] ?? null,
        //             'type'     => $inputs['type'] ?? null,
        //         ])->save();
        //         //队列去处理视频上传
        //         dispatch(new UploadVideo($video->id))->onQueue('videos');
        //     } else {
        //         return null;
        //     }
        // }

        //答题以前视图维护VideoKit系统，目前已有media系统
        // if (isset($inputs['uuid'])) {
        //     $videokitUser = VideokitUser::firstOrNew([
        //         'uuid'     => $inputs['uuid'],
        //         'video_id' => $video->id,
        //     ]);
        //     if (!isset($videokitUser->id)) {
        //         $videokitUser->save();
        //     }
        // }

        // return $video;
    }

    // 从答题兼容过来的repo, 带vod方法

    // 通过 VOD file_id 保存信息至 videos table
    public static function saveByVodFileId($fileId, User $user)
    {
        VodUtils::makeCoverAndSnapshots($fileId);
        $vodVideoInfo = VodUtils::getVideoInfo($fileId);
        return self::saveVodFile($user, $fileId, $vodVideoInfo);
    }

    // 根据 VODUtils 返回的信息创建 Video
    public static function saveVodFile(User $user, $fileId, array $videoFileInfo)
    {
        $url      = data_get($videoFileInfo, 'basicInfo.sourceVideoUrl');
        $cover    = data_get($videoFileInfo, 'basicInfo.coverUrl');
        $duration = data_get($videoFileInfo, 'basicInfo.duration');
        $height   = data_get($videoFileInfo, 'metaData.height');
        $width    = data_get($videoFileInfo, 'metaData.width');
        $hash     = md5_file($url);

        $video = new Video();

        $video->user_id  = $user->id;
        $video->disk     = 'vod';
        $video->hash     = $hash;
        $video->fileid   = $fileId;
        $video->path     = $url;
        $video->width    = $width;
        $video->height   = $height;
        $video->duration = $duration;
        $video->cover    = $cover;
        $video->json     = json_encode($videoFileInfo);
        $video->save();

        return $video;
    }

    /**
     * nova上传视频
     * 需要引入依赖：
     * "qcloud/vod-sdk-v5": "^2.4"
     * "qcloud/cos-sdk-v5": "*",
     * 删除"tencentcloud/tencentcloud-sdk-php": "3.0.94",
     */
    public static function uploadNovaVod($file)
    {
        $hash  = md5_file($file->getRealPath());
        $video = Video::firstOrNew([
            'hash' => $hash,
        ]);

        // 秒传
        if (isset($video->id) && isset($video->qcvod_fileid)) {
            return $video->id;
        }
        $video->save();

        $cosPath        = 'video/' . $video->id . '.mp4';
        $video->path    = $cosPath;
        $video->user_id = getUserId();
        $video->hash    = $hash;
        $video->title   = $file->getClientOriginalName();

        $video->disk = 'local'; //先标记为成功保存到本地
        $video->save();
        //  本地存一份用于上传
        $file->storeAs(
            'video',
            $video->id . '.mp4'
        );

        //vod上传配置
        $client = new \Vod\VodUploadClient(
            config("vod." . env('APP_NAME') . ".secret_id"),
            config("vod." . env('APP_NAME') . ".secret_key")
        );

        $client->setLogPath(storage_path('/logs/vod_upload.log'));
        try {
            $req = new VodUploadRequest();

            $req->MediaFilePath = storage_path('app/public/' . $video->path);

            $req->ClassId = config("vod." . env('APP_NAME') . ".class_id");

            $rsp = $client->upload("ap-guangzhou", $req);

            $localPath = $video->path;
            //上传成功
            echo "MediaUrl -> " . $rsp->MediaUrl . "\n";
            $video->disk         = 'vod';
            $video->qcvod_fileid = $rsp->FileId;
            $video->path         = $rsp->MediaUrl;
            $video->save(['timestamps' => false]);

            //获取截图
            $video->processVod();

            // 删除本地视频
            Storage::delete($localPath);
            return $video->id;
        } catch (\Exception $e) {
            // 处理上传异常
            Log::error($e);
            return;
        }
    }
}
