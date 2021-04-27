<?php

namespace Haxibiao\Media\Jobs;

use Haxibiao\Helpers\utils\FFMpegUtils;
use Haxibiao\Media\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use TencentCloud\Common\Credential;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Vod\V20180717\Models\PushUrlCacheRequest;
use TencentCloud\Vod\V20180717\VodClient;
use Vod\Model\VodUploadRequest;
use Vod\VodUploadClient;

/**
 * @deprecated 原来负责把本地磁盘的视频上传的job.. 现在不用了
 */
class UploadVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    const MAX_BACKUP_DAY = 7;

    public $tries = 2;

    protected $video;

    protected $publicDisk;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($videoId)
    {
        $this->video      = Video::find($videoId);
        $this->publicDisk = Storage::disk('public');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $video = $this->video;

        if (empty($video)) {
            return;
        }
        $publicDisk        = $this->publicDisk;
        $videoPath         = $video->path;
        $videoAbsolutePath = $publicDisk->path($videoPath);
        $canUpload         = $video->isDameiVideo() && $publicDisk->exists($videoPath);
        $videoSize         = $publicDisk->size($videoPath);

        if ($canUpload) {

            //上传到VOD
            $vodRsp = $this->uploadVod($videoAbsolutePath);

            if (!is_null($vodRsp)) {
                //处理视频 && 截图
                $processData               = $this->processVideo($video, $videoAbsolutePath);
                $processData['path']       = $vodRsp->MediaUrl;
                $processData['fileid']     = $vodRsp->FileId;
                $processData['disk']       = 'vod';
                $processData['json->size'] = $videoSize;
                $video->forceFill($processData);

                //VOD视频预热
                $this->pushUrlCacheRequest($vodRsp->MediaUrl);

                $video->syncStatus();
                $video->syncType();
                $video->save();
                //备份视频
                $this->backupVideo($videoPath);
                //清理备份
                $this->cleanBackup();
            }
            //写入日志
            $this->writeLog($video);
        }
    }

    /**
     * 上传到COS
     * @param $videoPath
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function uploadCos($videoPath)
    {
        $cosPath = 'storage/app/' . $videoPath;
        if (!is_prod_env()) {
            $cosPath = 'temp/' . $cosPath;
        }

        //上传成功
        if (Storage::cloud()->put($cosPath, $this->publicDisk->get($videoPath))) {
            return $cosPath;
        }
    }

    /**
     * 上传到VOD
     * @param $videoAbsolutePath
     * @return \Vod\Model\VodUploadResponse|null
     */
    protected function uploadVod($videoAbsolutePath)
    {
        //TODO: 答妹未及时同步media服务，应该有部分抖音粘贴视频未排重加入media.haxibiao.com

        //将视频上传到VOD
        $client = new VodUploadClient(env('VOD_SECRET_ID'), env('VOD_SECRET_KEY'));
        $client->setLogPath(storage_path('/logs/vod_upload.log'));
        $req                = new VodUploadRequest();
        $req->MediaFilePath = $videoAbsolutePath;
        $req->ClassId       = intval(env('VOD_CLASS_ID'));
        try {
            $rsp = $client->upload("ap-guangzhou", $req);
            return $rsp;
        } catch (\Exception $e) {
            // 处理上传异常
            Log::error($e);
        }
        return null;
    }

    /**
     * VOD视频预热
     * @param $url
     */
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

    /**
     * 处理视频信息 && 截图
     *
     * @param [type] $video
     * @param [type] $videoAbsolutePath
     * @return void
     */
    protected function processVideo($video, $videoAbsolutePath)
    {
        //解析视频信息
        $videoInfo = $this->resolveVideoInfo($videoAbsolutePath);
        $duration  = array_get($videoInfo, 'duration');

        //按百分比截取4张图片
        $timeCodes = [
            bcmul($duration, 0.2, 2),
            bcmul($duration, 0.4, 2),
            bcmul($duration, 0.6, 2),
            bcmul($duration, 0.8, 2),
        ];
        $duration = $duration > 0 ? ceil($duration) : $duration;
        foreach ($timeCodes as $second) {
            $imgName  = $video->hash . $second;
            $covers[] = FFMpegUtils::saveCover($videoAbsolutePath, $second, $imgName);
        }

        //填充数据
        $data = [
            'disk'           => 'cos',
            'json->duration' => $duration,
            'json->height'   => array_get($videoInfo, 'height'),
            'json->width'    => array_get($videoInfo, 'width'),
            'json->rotate'   => array_get($videoInfo, 'tags.rotate'),
            'json->covers'   => $covers,
            'json->cover'    => $covers[0],
        ];

        return $data;
    }

    /**
     * 解析视频信息
     *
     * @param [type] $videoPath
     * @return void
     */
    protected function resolveVideoInfo($videoPath)
    {
        try {
            return FFMpegUtils::getStreamInfo($videoPath);
        } catch (\Exception $ex) {
            return null;
        }
    }

    /**
     * 写入日志
     *
     * @param [type] $video
     * @return void
     */
    protected function writeLog($video)
    {
        $msg = $video->disk == 'cos' ? '上传成功' : '上传失败';
        $log = sprintf('Video ID:%s%s,播放地址:%s', $video->id, $msg, $video->url);
        info($log);
    }

    /**
     * 发布视频
     *
     * @param [type] $video
     * @return void
     */
    // protected function publishQuestion($video)
    // {
    //     $question = $video->question;
    //     if (!is_null($question) && !$question->isPublish()) {
    //         $question->submit = Question::SUBMITTED_SUBMIT;
    //         $question->save();
    //     }
    // }

    /**
     * 备份视频
     *
     * @param [type] $videoFile
     * @return void
     */
    protected function backupVideo($videoPath)
    {
        $baseName        = pathinfo($videoPath, PATHINFO_BASENAME);
        $backupDirectory = $this->makeBackupDirectory() . "/{$baseName}";

        try {
            $this->publicDisk->move($videoPath, $backupDirectory);
        } catch (\Exception $ex) {
            info($ex->getMessage());
        }
    }

    /**
     * 清理备份
     *
     * @return void
     */
    protected function cleanBackup()
    {
        $pastDay     = now()->subDay(self::MAX_BACKUP_DAY);
        $directories = $this->publicDisk->directories('videos');

        foreach ($directories as $directory) {
            $time = str_replace('videos/', '', $directory);
            //删除历史备份文件夹
            if ($pastDay->gte($time)) {
                $this->publicDisk->deleteDirectory($directory);
            }
        }
    }

    /**
     * 创建备份目录
     *
     * @return void
     */
    private function makeBackupDirectory()
    {
        $directory = sprintf('videos/%s', date('Y-m-d'));
        $this->publicDisk->makeDirectory($directory);
        return $directory;
    }
}
