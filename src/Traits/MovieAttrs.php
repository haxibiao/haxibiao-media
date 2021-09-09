<?php

namespace Haxibiao\Media\Traits;

use App\Movie;
use App\MovieSource;
use App\User;
use Haxibiao\Media\MovieHistory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

trait MovieAttrs
{
    /**
     * 状态文本
     */
    public function getStateAttribute()
    {
        return data_get(Movie::getStatuses(), $this->status);
    }

    public function getDataAttribute($value)
    {
        if (is_string($value)) {
            $value = json_decode($value);
        }

        if (empty($value) || count($value) < 1) {
            if ($source = $this->play_lines[0]) {
                return $source->data ?? [];
            }
        }
        return $value;
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
        $play_lines = json_decode($this->getRawOriginal('play_lines'));
        if (empty($play_lines)) {
            //其他线路 —— 这个写法可以慢慢淘汰
            $lines = [];
            if ($data_source = $this->data_source) {
                if (is_array($data_source) && count($data_source)) {
                    foreach ($data_source as $line => $source) {
                        $lines[] = [
                            'name' => $line,
                            'data' => $source,
                        ];
                    }
                }
            }
            if (empty($lines)) {
                //兼容没有路线表的项目
                if (Schema::hasTable('movie_sources')) {
                    $movieSources = MovieSource::where('movie_id', $this->id)->get();
                    foreach ($movieSources as $movieSource) {
                        $lines[] = [
                            'name' => $movieSource->name,
                            'url'  => $movieSource->url,
                            'data' => $movieSource->play_urls,
                        ];
                    }
                }
                $line[] = [
                    'name' => "默认",
                    'data' => $this->series,
                ];
            }
            return $lines;
        }
        return $play_lines;
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
     * 剧集信息
     *
     * @return array
     */
    public function getSeriesAttribute()
    {
        // // 兼容内涵电影代码用 series属性(serie对象的数组)写逻辑的部分
        // if (isset($this->attributes['series']) && is_array($this->attributes['series']) && count($this->attributes['series'])) {
        //     return $this->attributes['series'];
        // }

        //转换data的数组为serie对象数组
        $series      = [];
        $data_series = is_array($this->data) ? $this->data : @json_decode($this->data, true) ?? [];
        foreach ($data_series as $data_serie) {
            $series[] = $data_serie;
            //暂时没线路修复逻辑...
        }

        if ($user = currentUser()) {
            //获取APP用户观看进度记录
            $seriesHistories = \App\MovieHistory::where('user_id', $user->id)
                ->where('movie_id', $this->id)
                ->get();
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
            if ($user = currentUser()) {
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
            if ($user = currentUser()) {
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
                // 不用马甲运营账户
                $user = User::where('id', '<', 100)->inRandomOrder()->first();
                if ($user) {
                    $cache->put($cacheKey, $user->id, today()->addDays(3));
                    return $user;
                }
            }
        }
        //总有避风港用户(seed安装时已初始化3个测试用户)
        return $user ?? User::find(3);
    }
}
