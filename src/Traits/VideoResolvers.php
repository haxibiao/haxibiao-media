<?php
namespace Haxibiao\Media\Traits;

use App\Exceptions\UserException;
use App\Gold;
use App\Share;
use App\Visit;
use GraphQL\Type\Definition\ResolveInfo;
use Haxibiao\Media\Video;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

trait VideoResolvers
{
    /**
     * @deprecated 需要尽快迁移为posts来走答赚的fastRecommend, 暂时兼容旧的视频刷接口
     */
    public static function resolveRecommendVideos($root, $args, $context, $info)
    {
        $user = checkUser();
        return Video::getVideos($user, $args['limit'], $args['offset']);
    }

    public static function videoPlayReward($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user   = getUser();
        $inputs = $args['input'];

        $countReward = Gold::whereUserId($user->id)->whereBetWeen('created_at', [today(), today()->addDay()])->count('id');
        if ($countReward > 500) {
            return null;
        }

        //兼容老接口
        if (isset($inputs['video_id'])) {
            $video = Video::find($inputs['video_id']);

            if (is_null($video)) {
                throw new UserException('视频不存在,请刷新后再试！');
            }

            $visited = $user->visitedArticles()->where('visited_id', $video->article->id)->first();
            if (!is_null($visited)) {
                return null;
            }

            // 判断用户最近15秒内有没有看视频，防止重刷
            if ($gold = $user->golds()->latest()->first()) {
                if ($gold->created_at->diffInRealSeconds(now()) < 15) {
                    return null;
                }
            }

            //随机奖励50~100

            //四舍五入 14.9  = 15
            $playDuration = round($inputs['play_duration']);

            /**
             * 奖励机制,详情见(http://pm2.haxibiao.com:8080/browse/JK-49)
             * 观看大于等于1分钟奖励 333医宝
             * 观看大于等于30秒 奖励 150医宝
             * 观看大于等于15秒 奖励 70医宝
             */
            $rewardGold = 0;
            if ($playDuration >= 60) {
                $rewardGold = 333;
            } else if ($playDuration >= 30) {
                $rewardGold = 150;
            } else if ($playDuration >= 15) {
                $rewardGold = 70;
            }

            //观看失败 或 观看时长不足
            if ($rewardGold <= 0) {
                return null;
            }

            Visit::createVisit($user->id, $video->article->id, 'articles');

            $remark = sprintf('<%s>观看奖励', $video->id);
            // $gold   = Gold::makeIncome($user, $rewardGold, $remark);
            $user->goldWallet->changeGold($rewardGold, $remark);

            return $gold;
        }

        $video_ids  = $inputs['video_ids'];
        $rewardGold = random_int(5, 10);

        //大致统计用户浏览历史
        foreach ($video_ids as $video_id) {
            Visit::createVisit($user->id, $video_id, 'videos');
        }

        $remark = sprintf('视频观看时长奖励');
        // $gold   = Gold::makeIncome($user, $rewardGold, $remark);
        $gold = $user->goldWallet->changeGold($rewardGold, $remark);

        return $gold;
    }

    public static function queryDetail($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        return '增加贡献的场景:
1.奖励任务看视频赚钱,获得(+2*N贡献)
2.日常任务和奖励任务,获得(+2*N贡献)
3.刷视频时,查看广告视频得(+2*N贡献)
4.动态广场,查看广告动态得(+1*N贡献)';
    }

    public function downloadVideo($rootValue, $args, $context, $resolveInfo)
    {
        $videoId   = data_get($args, 'video_id');
        $video     = \App\Video::findOrFail($videoId);
        $user      = getUser();
        $originUrl = $video->path;

        // 之前下载过,不需要重复解析
        $share = Share::where('user_id', $user->id)
            ->where('shareable_id', $video->id)
            ->where('shareable_type', 'videos')
            ->where('active', true)
            ->first();
        if ($share) {
            return $share->url;
        }

        $share = Share::buildFor($video)
            ->setActive(false)
            ->setUrl($originUrl)
            ->setUserId($user->id)
            ->build();

        // 请求处理哈希云进行MetaData处理
        $uuid           = $share->uuid;
        $title2MetaData = sprintf('uuid:%s', $uuid);
        $curl           = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL            => \Haxibiao\Media\Video::getMediaBaseUri() . "api/video/modifyMetadata",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => "POST",
            CURLOPT_POSTFIELDS     => [
                'title' => $title2MetaData,
                'url'   => $originUrl,
            ],
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $result = json_decode($response);

        $responseCode = data_get($result, 'code');
        $modifiedUrl  = data_get($result, 'data.MediaUrl');
        if ($responseCode == 200 && $modifiedUrl) {
            $share->url    = $modifiedUrl;
            $share->active = true;
            $share->save();

            return $modifiedUrl;
        }
        throw new UserException('下载失败');
    }
}
