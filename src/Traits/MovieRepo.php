<?php

namespace Haxibiao\Media\Traits;

use GuzzleHttp\Client;
use Haxibiao\Breeze\Exceptions\GQLException;
use Haxibiao\Media\Movie;
use Haxibiao\Media\Video;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use MeiliSearch\Client as MeiliSearchClient;

trait MovieRepo
{
    public static function addMeiliSearchIndex(Movie $movie)
    {
        if(config('media.meilisearch.enable')){
            $masterKey = env('MEILISEARCH_KEY');
            $host = env('MEILISEARCH_HOST');
            if (empty($masterKey)) {
                info("请先在 .env 中补充 'MEILISEARCH_KEY'");
            }
            if (empty($host)) {
                info("请先在 .env 中补充 'MEILISEARCH_HOST' ");
            }
            $client = new Client($host, $masterKey);
            $index = $client->index(config('app.name'));
            $documents = [];
            $documents[] = [
                'name' => $movie->name,
                'id'   => $movie->id,
            ];
            $result = $index->addDocuments($documents);
            $updateID = $result['updateId'];
            info("补充搜索词成功！！:$updateID");
        }
    }

    public static function addMeiliSearch(Movie $movie)
    {
        if (config('media.meilisearch.enable')) {
            $client      = new MeiliSearchClient(config('media.meilisearch.host'), config('media.meilisearch.key'));
            $index       = $client->index(config('media.meilisearch.name'));
            $documents[] = [
                'name' => $movie->name,
                'id'   => $movie->id,
            ];
            $result   = $index->addDocuments($documents);
            $updateID = $result['updateId'];
            return $updateID;
        }
    }

    /**
     * 保存影片封面
     */
    public function saveCover(UploadedFile $file)
    {
        //影片封面文件名
        $filename = sprintf("%s.%s", $this->id . "_" . time(), 'png');
        $folder   = storage_folder('movies');
        $file->storeAs($folder, $filename);
        $cloud_path = sprintf("%s/%s", $folder, $filename);
        $cdnurl     = cdnurl($cloud_path);
        return $cdnurl;
    }

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

    /**
     * 剪辑返回新m3u8内容
     * $targetM3u8 目标影片
     * $startTime 剪辑开始时间
     * $endTime 剪辑结束时间
     */
    public static function clipM3u8($targetM3u8, $startTime, $endTime)
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
     * 存储剪辑影片到动态 - 用api方式了
     * @deprecated
     */
    public static function storeClipMovie($user, $movie, $m3u8, $postTitle, $seriseName)
    {
        // 文件名 = source_key + 当前时间戳.m3u8
        $filename    = $movie->source_key . '-' . time() . ".m3u8";
        $cdn         = rand_pick_ucdn_domain();
        $newM3u8Path = 'storage/app-' . env('APP_NAME') . '/clip/' . $filename;
        $playUrl     = Storage::url($newM3u8Path);
        Storage::cloud()->put($newM3u8Path, $m3u8, 'public');
        // 计算视频时长
        preg_match_all('/\d+[.]\d+/', $m3u8, $arr);
        $duration = array_sum($arr[0]);
        $duration = (int) $duration;
        // 存储成视频
        $video = new Video([
            'user_id'  => $user->id,
            'duration' => $duration,
            'disk'     => config('filesystems.cloud'),
            'path'     => $newM3u8Path,
            'title'    => $postTitle,
        ]);
        //触发 VideoObserver 维护内容关系
        $video->save();

        //同步到内涵云vidoes
        DB::connection('mediachain')->table('videos')->insert([
            'movie_key'   => $movie->source_key,
            'source_name' => $seriseName,
            'duration'    => $duration,
            'url'         => $playUrl,
            'title'       => $postTitle,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
        return $video->post;
    }

    public static function findSeriesIndexByM3u8($movie, $m3u8)
    {
        foreach ($movie->series as $i => $item) {
            if ($item['url'] == $m3u8) {
                return $i;
            }
        }
    }

    /**
     * 长剪短
     */
    public static function clipMovie($user, $movie, $m3u8, $startTime, $endTime, $title, $series_index)
    {
        $endPoint    = get_neihancloud_api() . '/api/clip?';
        $requestArgs = [
            'm3u8'        => $m3u8,
            'video_title' => $title,
            'end_time'    => $endTime,
            'start_time'  => $startTime,
            'series_name' => $series_index,
            'movie_key'   => $movie->movie_key ?? $movie->id,
            'callbackurl' => config('app.url') . '/api/movie/update_video_cover',
        ];
        $url    = $endPoint . http_build_query($requestArgs);
        $result = json_decode(file_get_contents($url), true);
        if ($result['status'] == 200) {
            $video    = $result['data'];
            $clipInfo = (object) [
                'start_time'   => $startTime,
                'end_time'     => $endTime,
                'series_index' => $series_index, //就存剧集index
            ];

            // 创建剪辑的视频
            $video = new Video([
                'user_id'   => $user->id,
                'movie_id'  => $movie->id,
                'movie_key' => $movie->movie_key, //关联影片云端唯一标识
                'title' => $title, //剪辑配问
                'duration' => $video['duration'] ?? 15,
                'disk'      => 'othermovie',
                'path'      => $video['url'],
            ]);
            $video->json = $clipInfo;
            $video->save();

            //movie计数剪辑数count_clip
            $movie->count_clips = $movie->videos()->count();
            $movie->save();

            //此处代码已重构 到VideoObserver触发自动发布内容

            return $video;
        } else {
            throw new GQLException('剪辑失败！');
        }
    }

    /**
     * 根据m3u8地址获取目标集名字
     */
    public function findSeriesName($playLine, $seriesIndex)
    {
        $lines  = is_array($this->data_source) ? $this->data_source : @json_decode($this->data_source, true) ?? [];
        $series = $lines[$playLine] ?? [];
        return data_get($series, "$seriesIndex.name");
    }

    /**
     * 更新剧集的播放地址
     *
     * @param Movie $movie 影片
     * @param string $name 剧集名
     * @param string $url 播放地址
     * @return void
     */
    public static function updateSeries($movie, $name, $url)
    {
        if (!blank($name)) {
            $series_raw = $movie->series;
            $series     = [];
            $updated    = false;
            foreach ($series_raw as $item) {
                $raw_name = $item['name'] ?? '';
                if ($raw_name == $name) {
                    //更新
                    $item['url'] = $url;
                    $updated     = true;
                }

                //剔除以前的混乱的剧集名
                if (!preg_match("/第\d集/", $raw_name)) {
                    continue;
                }

                $series[] = $item;
            }
            if (!$updated) {
                //增加
                $series[] = [
                    'name' => $name,
                    'url'  => $url,
                ];
            }
            //保存data
            $movie->data = $series;
        }
        $movie->save();
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

    public static function resourceSearch($keyword, $page = 1, $perPage = 10)
    {
        //去哈希云请求search电影
        $client   = new Client();
        $response = $client->request('GET', 'https://neihancloud.com/api/resource/search', [
            'http_errors' => false,
            'query'       => [
                'keyword' => $keyword,
                'page'    => $page,
                'perPage' => $perPage,
            ],
        ]);
        throw_if($response->getStatusCode() == 404, GQLException::class, '搜索开小差啦，请稍后再试吧!');
        $contents = $response->getBody()->getContents();
        if (!empty($contents)) {
            $contents = @json_decode($contents);
            return $contents;
        }
        return null;
    }
}
