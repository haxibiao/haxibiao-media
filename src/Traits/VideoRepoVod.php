<?php

namespace Haxibiao\Media\Traits;

//过期的一些VOD 函数
use Haxibiao\Helpers\utils\QcloudUtils;

/**
 * 从工厂APP里过来的trait 处理vod相关
 */
trait VideoRepoVod
{

    public function syncVodProcessResult()
    {
        //FIXME: 注意 vod 相关的代码，统一维护到haxiyun
    }

    public function publishPost()
    {
        //获得封面了，关联的视频动态发布出去
        if (!empty($this->cover)) {
            if ($article = $this->article) {
                //文章发布
                $article->status = 1;
                $article->save();

                //视频发布
                $this->status = 1;
                $this->save();
            }
        }
    }

    public function makeCover()
    {
        QcloudUtils::makeCoverAndSnapshots($this->fileid, $this->duration);
    }

    public function transCode()
    {
        QcloudUtils::convertVodFile($this->fileid, $this->duration);
    }

    public function startProcess()
    {
        //截图前需要先获取到duration
        $this->syncVodProcessResult();
        sleep(1); //vod:api 请求频率限制
        //截图
        $this->makeCover();
        sleep(1); //vod:api 请求频率限制
        //转码
        $this->transCode();
        sleep(1); //vod:api 请求频率限制
    }

    public function processVod()
    {
        set_time_limit(600); //queue worker 的timeout 最长就这么长了

        if (!$this->duration) {
            $this->startProcess();
        }
        sleep(5); //5秒后检查

        //15秒内重复检查截图结果
        for ($i = 0; $i < 3; $i++) {
            //同步上传后的信息,获得封面，宽高
            $flag = $this->syncVodProcessResult();
            //有截图就发布
            if ($flag >= 1) {
                $this->publishPost();
                break;
            }
            //这里重复提交截图job是因为几秒的短视频截图不稳定
            $this->makeCover();
            sleep(5);
        }

        //5分钟内尝试30次获取转码结果,目前发现1分钟短视频转码时间不到1分钟...
        for ($i = 0; $i < 30; $i++) {
            //同步上传后的转码结果
            $flag = $this->syncVodProcessResult();
            if ($flag == 2) {
                break;
            }
            sleep(10);
        }
    }
}
