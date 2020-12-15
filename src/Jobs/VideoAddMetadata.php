<?php

namespace Haxibiao\Media\Jobs;

use Haxibiao\Helpers\utils\FFMpegUtils;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Vod\Model\VodUploadRequest;
use Vod\VodUploadClient;

/**
 * 给视频文件添加metadata信息
 */
class VideoAddMetadata implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $video;

    public function __construct($video)
    {
        $this->video = $video;
    }

    public function handle()
    {
        $video = $this->video;

        $path = $video->path;
        $vid  = $this->getVidFromBinaryStream($path);

        // 文件流中没有vid
        if (!$vid) {
            $vid      = $this->generateVid();
            $metadata = 'vid:' . $vid;

            $localPath = FFMpegUtils::addMediaMetadata($path, $metadata);
            $cover     = $this->downloadImage2Local($video->cover);
            $result    = $this->uploadVideo2Vod($localPath, $cover);
            if (!$result) {
                return false;
            }

            if (Schema::hasColumn('videos', 'qcvod_fileid')) {
                $video->qcvod_fileid = data_get($result, 'fileid');
            } else {
                $video->fileid = data_get($result, 'fileid');
            }

            $video->path = data_get($result, 'path');
        }
        $video->vid = $vid;
        $video->saveDataOnly();
    }

    private function downloadImage2Local($url)
    {
        $coverFileName = Str::random(12) . '.jpg';
        // 输出文件放系统临时文件夹，随系统自动清理
        $outputCoverFilePath = sys_get_temp_dir() . '/' . $coverFileName;
        file_put_contents($outputCoverFilePath, file_get_contents($url));
        return $outputCoverFilePath;
    }

    /**
     * 上传视频到vod
     */
    private function uploadVideo2Vod($localPath, $cover = null)
    {
        $client             = new VodUploadClient(env('VOD_SECRET_ID'), env('VOD_SECRET_KEY'));
        $req                = new VodUploadRequest();
        $req->MediaFilePath = $localPath;
        $req->CoverFilePath = $cover;
        try {
            $rsp = $client->upload("ap-guangzhou", $req);
            return [
                'fileid' => $rsp->FileId,
                'path'   => $rsp->MediaUrl,
            ];
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 生成vid
     */
    private function generateVid()
    {
        $hash = $this->video->hash;

        $strLength  = Str::length($hash);
        $diffLength = 32 - $strLength; // 32代表前端取去vid:后32字符

        if ($diffLength == 0) {
            return $hash;
            // 补足32位
        } else if ($diffLength > 0) {
            return $hash . Str::random($diffLength);
        }
        return Str::limit($diffLength, 32, '');
    }

    /**
     * 从文件流中获取vid
     */
    private function getVidFromBinaryStream($path)
    {
        $ch      = curl_init();
        $headers = [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'Accept-Encoding: gzip, deflate, br',
            'Accept-Language: en-US,en;q=0.9',
            'Range: bytes=0-200000',
        ];

        $options = array(
            CURLOPT_URL            => $path,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:28.0) Gecko/20100101 Firefox/28.0',
            CURLOPT_ENCODING       => "utf-8",
            CURLOPT_AUTOREFERER    => false,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_MAXREDIRS      => 10,
        );
        curl_setopt_array($ch, $options);
        $data = curl_exec($ch);
        curl_close($ch);
        $tmp = explode("vid:", $data);
        $key = '';
        if (count($tmp) > 1) {
            $key = trim(explode("%", $tmp[1])[0]);
        }
        return $key;
    }
}
