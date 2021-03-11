<?php

namespace Haxibiao\Media\Traits;

use App\Post;
use App\Video;
use Haxibiao\Media\Movie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

trait MovieRepo
{
    public static function getCDNDomain($bucket)
    {
        return data_get(space_ucdn_map(), $bucket);
    }

    public function toResource()
    {
        $data = $this->toArray();
        unset($data['cover']);
        return array_merge($data, [
            'cover'  => $this->cover_url,
            'region' => $this->type_name_attr,
        ]);
    }

    public static function getStatus()
    {
        return [
            Movie::PUBLISH  => '可播放',
            Movie::DISABLED => '禁用',
            Movie::ERROR    => '资源错误',
        ];
    }

    /**
     * 影片剪辑，返回新m3u8内容
     * $targetM3u8 目标影片
     * $startTime 剪辑开始时间
     * $endTime 剪辑结束时间
     */
    public static function ClipMovie($targetM3u8, $startTime, $endTime)
    {
        $content    = file_get_contents($targetM3u8);
        $m3u8Prefix = substr($content, 0, stripos($content, "#EXTINF"));
        $tsList     = substr($content, stripos($content, "#EXTINF"));
        $tsList     = str_replace("#EXT-X-ENDLIST\n", '', $tsList);
        $tsList     = explode('#EXTINF:', $tsList);
        if (empty($tsList[0])) {
            unset($tsList[0]);
        }
        $startTsTime = 0;
        $newTSList   = "";
        foreach ($tsList as $index => $ts) {
            $ts = str_replace("\n", '', $ts);
            // 获取ts时长和ts路径
            list($time, $url) = explode(',', $ts);
            $time             = (double) $time;
            // 累积新视频总时长
            $startTsTime = $startTsTime + $time;
            if ($startTime >= $startTsTime) {
                // 还没到指定裁剪TS
                continue;
            }
            // 拼接新ts路径
            $newTSList = $newTSList . "#EXTINF:{$time},\n" . "{$url}\n";
            // 指定结束时间已大于总视频时长
            if ($startTsTime >= $endTime) {
                break;
            }
        }
        // 拼接结尾
        $newIndexM3u8 = $m3u8Prefix . $newTSList . "#EXT-X-ENDLIST\n";
        return $newIndexM3u8;
    }

    /**
     * 存储剪辑影片到动态
     */
    public static function storeClipMovie($user, $movie, $m3u8, $postTitle, $seriseName)
    {
        // 文件名 = source_key + 当前时间戳.m3u8
        $filename    = $movie->source_key . '-' . time() . ".m3u8";
        $cdn         = rand_pick_ucdn_domain();
        $newM3u8Path = '/clip/' . $filename;
        // 影片剪辑都存储到 othermovie bucket 里面
        $playUrl = "{$cdn}m3u8/othermovie{$newM3u8Path}";
        Storage::disk('othermovie')->put($newM3u8Path, $m3u8, 'public');
        // 计算视频时长
        preg_match_all('/\d+[.]\d+/', $m3u8, $arr);
        $duration = array_sum($arr[0]);
        $duration = (int) $duration;
        // 存储成视频
        $video = Video::create([
            'user_id'  => $user->id,
            'duration' => $duration,
            'disk'     => 'othermovie',
            'path'     => $playUrl,
        ]);
        // 存储成动态
        $post = Post::create([
            'user_id'     => $user->id,
            'video_id'    => $video->id,
            'description' => $postTitle,
            'movie_id'    => $movie->id,
        ]);
        DB::connection('mediachain')->table('videos')->insert([
            'movie_key'   => $movie->source_key,
            'source_name' => $seriseName,
            'duration'    => $duration,
            'url'         => $playUrl,
            'title'       => $postTitle,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
        return $post;
    }

    /**
     * 根据m3u8地址获取目标集名字
     */
    public static function findSeriesName($m3u8, $movie)
    {
        $series = $movie->data;
        foreach ($series as $item) {
            if ($item->url == $m3u8) {
                return $item->name;
            }
        }
    }

    public static function getCategories()
    {
        return [
            Movie::CATEGORY_RI       => '日剧',
            Movie::CATEGORY_MEI      => '美剧',
            Movie::CATEGORY_HAN      => '韩剧',
            Movie::CATEGORY_GANG     => '港剧',
            Movie::CATEGORY_TAI      => '泰剧',
            Movie::CATEGORY_YIN      => '印度剧',
            Movie::CATEGORY_BLGL     => '同性恋',
            Movie::CATEGORY_JIESHUO  => '解说',
            Movie::CATEGORY_ZHONGGUO => '中国',
            Movie::CATEGORY_HOT      => '热门/热播',
            Movie::CATEGORY_NEWST    => '最新',
        ];
    }
}
