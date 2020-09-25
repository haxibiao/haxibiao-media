<?php


namespace Haxibiao\Media\Console;

use Haxibiao\Media\Video;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PharData;
use TencentCloud\Common\Credential;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Vod\V20180717\VodClient;
use TencentCloud\Vod\V20180717\Models\DescribeCdnLogsRequest;
use Illuminate\Console\Command;


class CountVideoViewsCommand  extends Command
{
    // 缓存的命名空间
    const CACHE_KET = 'count_video_views_%s';

    protected $signature = 'haxibiao:video:CountVideoViewers';

    protected $description = '以天为单位更新视频总播放量(可重复执行)';

    public function handle()
    {
        $enabled = config('media.enabled_statistics_video_views',false);
        if(!$enabled){
            $this->info('不能完成该操作,没有开启日播放量统计开关～');
        }

        $this->info('start count vod view count');

        $startTime  = Carbon::yesterday()->toIso8601String();
        $endTime    = Carbon::yesterday()->endOfDay()->toIso8601String();
        $domainName = "1254284941.vod2.myqcloud.com";

        // 获取日志列表
        $result = $this->getDescribeCdnLogs($domainName, $startTime, $endTime);
        $result = json_decode($result,true);
        $logUrls = array_pluck(data_get($result,'DomesticCdnLogs'),'Url');

        // 下载 cdn logs
        $logs = $this->downloadCdnLogs($logUrls);

        // 计算今日播放量
        $this->cacheVideoViewers($logs);

        // 统计视频总播放量
        $this->recordVideoViewers();

        // 删除本地 cdn logs
        $this->deleteLogs();

        $this->info('end count vod view count');
    }

    private function getDescribeCdnLogs($domainName,$startTime,$endTime){
        $this->info('获取CDN日志');
        try {
            $secretId  = config('vod.' . config('app.name') . '.secret_id');
            $secretKey = config('vod.' . config('app.name') . '.secret_key');

            $cred = new Credential($secretId, $secretKey);
            $httpProfile = new HttpProfile();
            $httpProfile->setEndpoint("vod.tencentcloudapi.com");

            $clientProfile = new ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);
            $client = new VodClient($cred, "", $clientProfile);

            $req = new DescribeCdnLogsRequest();

            $params = array(
                "DomainName" => $domainName,
                "StartTime" => $startTime,
                "EndTime" => $endTime
            );
            $req->fromJsonString(json_encode($params));

            $resp = $client->DescribeCdnLogs($req);

            return $resp->toJsonString();
        }
        catch(TencentCloudSDKException $e) {
            echo $e;
        }
    }

    private function downloadCdnLogs($logUrls){
        $this->info('下载CDN日志');
        $logs = [];
        foreach ($logUrls as $logUrl){
            $pathInfo = pathinfo($logUrl);
            $filename = data_get($pathInfo,'filename');
            $suffix   = 'gz';
            $prefix   = 'cdnlogs';
            $filename = $prefix .'/'. $filename . '.' .$suffix;

            Storage::disk('local')->put($filename,file_get_contents($logUrl));
            $gzPath = Storage::disk('local')->path($filename);

            // .gz中提取文件
            $bufferSize = 4096; // read 4kb at a time
            $outFileName = str_replace('.gz', '', $gzPath);
            $file = gzopen($gzPath, 'rb');
            $outFile = fopen($outFileName, 'wb');
            while (!gzeof($file)) {
                fwrite($outFile, gzread($file, $bufferSize));
            }
            fclose($outFile);
            gzclose($file);

            $logs[] = $outFileName;
        }
        return $logs;
    }

    private function cacheVideoViewers($logs){
        $this->info('统计今日视频的播放量');

        // 不重复统计
        $isOver = cache()->get(sprintf(self::CACHE_KET,'yesterday_count_over'),false);
        if($isOver){
            return;
        }

        $ttl = Carbon::today()->endOfDay()->diffInMinutes(now())*100;

        foreach ($logs as $log){
            $lines = file($log);
            foreach ($lines as $line){
                $this->info($line);
                $columns = explode(' ',$line);

                $ip = data_get($columns,1);

                // vod fileid
                $fileid = substr(Str::beforeLast(data_get($columns,3),'/'),-19);

                $cacheKey = sprintf(self::CACHE_KET,$fileid);

                if(cache()->has($cacheKey)){
                    $ips = cache()->get($cacheKey);
                    if(!in_array($ip,$ips)){
                        $ips[] = $ip;
                        cache()->put($cacheKey,$ips,$ttl);
                    }
                } else {
                    cache()->put($cacheKey,[$ip],$ttl);
                }
            }
        }
        cache()->put(sprintf(self::CACHE_KET,'yesterday_count_over'),true,$ttl);
    }

    private function recordVideoViewers(){
        $this->info('开始统计总下载量');

        // 不会重复叠加数据
        $minId = cache()->get(sprintf(self::CACHE_KET,'yesterday_record_min_id'),0);
        $ttl = Carbon::today()->endOfDay()->diffInMinutes(now())*100;

        Video::whereNotNull('qcvod_fileid')->where('id','>',$minId)->chunk(100,function ($videos)use($ttl){
            foreach ($videos as $video){
                $fileid = data_get($video,'qcvod_fileid');
                $ips = cache()->get(sprintf(self::CACHE_KET,$fileid),[]);
                $yesterdayViewsCount = count($ips);

                if($yesterdayViewsCount == 0){
                    continue;
                }
                $json   = data_get($video,'json',[]);
                $count_views   = data_get($video,'json.count_views',0);
                $count_views += $yesterdayViewsCount;
                $json->count_views = $count_views;
                $video->json = $json;
                $video->saveDataOnly();

                cache()->put(sprintf(self::CACHE_KET,'yesterday_record_min_id'),$video->id,$ttl);
                $this->info($video->id . ':' . $count_views);
            }
        });
    }

    private function deleteLogs(){
        return Storage::disk('local')->deleteDirectory('cdnlogs');
    }

}