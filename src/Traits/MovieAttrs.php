<?php

namespace Haxibiao\Media\Traits;

use App\Movie;
use App\User;
use Haxibiao\Media\MovieHistory;
use Illuminate\Support\Facades\Cache;

trait MovieAttrs
{
    /**
     * 状态文本
     */
    public function getStateAttribute()
    {
        return data_get(Movie::getStatuses(), $this->status);
    }

    public function getIntroductionAttribute()
    {
        $attr = $this->attributes["introduction"] ?? '';
        $str  = preg_replace("/<(\/?span.*?)>/si", "", $attr);
        return $str;
    }

    /**
     * 影片线路
     */
    public function getPlayLinesAttribute()
    {
        $lines = [];
        $movie = $this;

        $lines[] = [
            'name' => "默认",
            'data' => $movie->series_urls,
        ];

        //FIXME: 下线北美线路，后面补充香港线路，我们的非ucdn线路
        // $lines[] = [
        //     'name' => "北美",
        //     'data' => json_decode($movie->data_source, true),
        // ];

        return $lines;
    }

    public function getUrlAttribute()
    {
        $path = '/%s/%d';
        $path = sprintf($path, 'movie', $this->id);
        return url($path);
    }

    public function getCoverUrlAttribute()
    {
        return $this->cover;
    }

    public function getRegionNameAttribute()
    {
        return $this->region;
    }

    public function getIsViewedAttribute()
    {
        if ($user = currentUser()) {
            return MovieHistory::where([
                'user_id'  => $user->id,
                'movie_id' => $this->id,
            ])->exists();
        }
        return false;
    }

    /**
     * 默认的第一集播放地址
     */
    public function getPlayUrlAttribute()
    {
        $fallback_url = "http://cdn-iqiyi-com.diudie.com/series/70177/index.m3u8";
        return $this->data[0]["url"] ?? $fallback_url;
    }

    /**
     * 电影剧集+播放地址
     */
    public function getSeriesUrlsAttribute()
    {
        //避免 casts appends 对 data属性的影响破坏了剧集播放源关键接口
        $raw_data   = $this->getRawOriginal('data');
        $raw_series = json_decode($raw_data, true) ?? [];

        $series = [];
        foreach ($raw_series as $item) {
            // //基于内涵云已预热加速的URL，无需再用之前的m3u8微服务处理回源space了
            // $ucdn_domain = parse_url($item['url'], PHP_URL_HOST);
            // $ucdn_root   = "https://" . $ucdn_domain . "/";
            // $space       = get_space_by_ucdn($ucdn_root);
            // $space_path  = parse_url($item['url'], PHP_URL_PATH);
            // if (str_contains($space_path, ".m3u8")) {
            //     //旧的series URL: {加速域名}/{space}/{movie_id}/index.m3u8
            //     //新的负载均衡型的HK回源的 series URL:  {加速域名}/m3u8/{space}/{movie_id}/index.m3u8
            //     $item['url'] = "https://$ucdn_domain/m3u8/$space$space_path";
            // }
            $series[] = $item;
        }
        return $series;
    }

    /**
     * 剧集信息
     *
     * @return array
     */
    public function getSeriesAttribute()
    {
        // 兼容内涵电影代码用 series属性(serie对象的数组)写逻辑的部分
        if (isset($this->attributes['series']) && is_array($this->attributes['series'])) {
            return $this->attributes['series'];
        }

        //转换data的数组为serie对象数组
        $series      = [];
        $data_series = $this->getSeriesUrlsAttribute();
        foreach ($data_series as $data_serie) {
            $series[] = $data_serie;
        }
        return $series;
    }

    public function getDataAttribute()
    {
        //重用加载多线路的
        $series = $this->getSeriesUrlsAttribute();

        //app 访问这里, 填充已观看进度信息
        if ($user = getUser(false)) {
            //获取观看进度记录
            $seriesHistories = \App\MovieHistory::where('user_id', $user->id)
                ->where('movie_id', $this->id)->get();
            foreach ($seriesHistories as $seriesHistory) {
                $index = $seriesHistory->series_id;
                //修复观看历史数据对不上的脏数据异常
                $serie = $series[$index] ?? null;
                if ($serie && isset($serie->progress)) {
                    $serie->progress = $seriesHistory->progress;
                }
            }
        }
        return $series;
    }

    public function getCreatedAtAttribute()
    {
        $createdAt = $this->attributes['created_at'];
        return $createdAt;
    }

    public function getFavoritedAttribute()
    {
        //复用favorable的特性属性
        return $this->is_favorited;
    }

    public function getLastWatchSeriesAttribute()
    {
        //性能优化: 仅查询详情页sns状态信息时执行
        if (request('fetch_sns_detail')) {
            if (currentUser()) {
                $user    = getUser();
                $history = MovieHistory::where([
                    'user_id'  => $user->id,
                    'movie_id' => $this->id,
                ])->latest()->first();
                if (isset($history)) {
                    return $history->series_id;
                }
            }
        }
        return null;
    }

    public function getLastWatchProgressAttribute()
    {
        //性能优化: 仅查询详情页sns状态信息时执行
        if (request('fetch_sns_detail')) {
            if (currentUser()) {
                $user    = getUser();
                $history = MovieHistory::where([
                    'user_id'  => $user->id,
                    'movie_id' => $this->id,
                ])->latest()->first();
                if (isset($history)) {
                    return $history->progress;
                }
            }
        }
        return null;
    }

    public function getCountFavoritesAttribute()
    {
        return $this->getRawOriginal('count_favorites');
    }

    public function getCountCommentsAttribute()
    {
        return $this->getRawOriginal('count_comments');
    }

    //伪装用户发布该电影，缓存三天为该用户发布
    public function getUserAttribute()
    {
        //优先尊重用求片者
        $user = $this->attributes['user'] ?? null;
        if (!$user && $this->user_id) {
            $user = User::find($this->user_id);
        }

        if (!$user) {
            $cache    = cache();
            $cacheKey = sprintf('movie:id:%s', $this->id);
            if ($cache->has($cacheKey)) {
                $user_id = $cache->get($cacheKey);
                return User::find($user_id);
            } else {
                $user = User::where('role_id', User::VEST_STATUS)->inRandomOrder()->first();
                if ($user) {
                    $cache->put($cacheKey, $user->id, today()->addDays(3));
                    return $user;
                }
            }
        }
        return $user;
    }
}
