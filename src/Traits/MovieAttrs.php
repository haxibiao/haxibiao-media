<?php

namespace Haxibiao\Media\Traits;

use App\User;
use Haxibiao\Media\MovieHistory;
use Illuminate\Support\Facades\Cache;

trait MovieAttrs
{
    public function getIntroductionAttribute()
    {
        $str = preg_replace("/<(\/?span.*?)>/si", "", $this->attributes["introduction"]);
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

        $lines[] = [
            'name' => "北美",
            'data' => json_decode($movie->data_source, true),
        ];

        return $lines;
    }

    /**
     * 电影剧集的播放地址(HK负载均衡)
     */
    public function getSeriesUrlsAttribute()
    {
        //避免 casts appends 对 data属性的影响破坏了剧集播放源关键接口
        $raw_data   = $this->getRawOriginal('data');
        $raw_series = json_decode($raw_data, true) ?? [];

        //旧的series URL: {加速域名}/{space}/{movie_id}/index.m3u8
        //新的负载型的series URL:  {加速域名}/m3u8/{space}/{movie_id}/index.m3u8
        $series = [];
        foreach ($raw_series as $item) {
            $ucdn_domain = parse_url($item['url'], PHP_URL_HOST);
            $ucdn_root   = "https://" . $ucdn_domain . "/";
            $space       = get_space_by_ucdn($ucdn_root);
            $space_path  = parse_url($item['url'], PHP_URL_PATH);
            $item['url'] = "https://$ucdn_domain/m3u8/$space$space_path";
            $series[]    = $item;
        }
        return $series;
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
        if ($user = checkUser()) {
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

    public function setDataAttribute($value)
    {
        if (is_string($value)) {
            $this->attributes['data'] = @json_decode($value);
        } else {
            $this->attributes['data'] = $value;
        }
    }

    public function getDataAttribute()
    {
        //重用加载多线路的
        $series = $this->getSeriesUrlsAttribute();

        //app 访问这里
        if ($user = getUser(false)) {
            //获取观看进度记录
            $seriesHistories = \App\MovieHistory::where('user_id', $user->id)
                ->where('movie_id', $this->id)->get();
            foreach ($seriesHistories as $seriesHistory) {
                $index                    = $seriesHistory->series_id;
                $series[$index]->progress = $seriesHistory->progress;
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
        //借用favorable的特性属性
        return $this->is_favorited;
    }

    public function getLastWatchSeriesAttribute()
    {
        if (checkUser()) {
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

    public function getLastWatchProgressAttribute()
    {
        if (checkUser()) {
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

    public function getCountFavoritesAttribute()
    {
        return $this->favorites()->count();
    }

    public function getCountCommentsAttribute()
    {
        return $this->comments()->count();
    }

    //伪装用户发布该电影，缓存三天为该用户发布
    public function getUserAttribute()
    {
        $cache    = Cache::store('redis');
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
        return null;
    }
}
