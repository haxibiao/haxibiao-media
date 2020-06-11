<?php

namespace haxibiao\media\Traits;

use App\User;
use App\Visit;
use App\Question;
use haxibiao\media\Video;
use Illuminate\Support\Arr;
use haxibiao\helpers\VodUtils;
use App\Exceptions\UserException;
use haxibiao\helpers\QcloudUtils;
use Illuminate\Http\UploadedFile;
use TencentCloud\Common\Credential;
use haxibiao\media\Jobs\MakeVideoCovers;
use TencentCloud\Vod\V20180717\VodClient;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Vod\V20180717\Models\PushUrlCacheRequest;

trait VideoRepo
{
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
        $client->PushUrlCache($req);
    }

    public function processVod()
    {

        $videoInfo      = QcloudUtils::getVideoInfo($this->qcvod_fileid);
        $duration       = Arr::get($videoInfo, 'basicInfo.duration');
        $sourceVideoUrl = Arr::get($videoInfo, 'basicInfo.sourceVideoUrl');
        $this->path     = $sourceVideoUrl;
        $this->duration = $duration;
        $this->disk     = 'vod';
        $this->hash     = hash_file('md5', $sourceVideoUrl);
        $this->save();

        //触发截图操作
        MakeVideoCovers::dispatchNow($this);
    }

    /**
     * @deprecated 答题废弃的视频刷接口，新版本gql需要用新的FastRecommand
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
            Visit::saveVisits($user, $videos,  Visit::FAKE_VISITED);
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
}
