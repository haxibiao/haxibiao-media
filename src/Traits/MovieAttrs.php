<?php

namespace Haxibiao\Media\Traits;

use App\Favorite;
use App\Movie;
use App\User;
use Hashids\Hashids;
use Haxibiao\Media\MovieHistory;
use Illuminate\Support\Facades\Cache;

trait MovieAttrs
{
    public function getSlugAttribute()
    {
        $domain  = request()->getHost();
        $hashids = new Hashids($domain);
        return $hashids->encode($this->getRawOriginal('movie_key'));
    }

    public function getIdAttribute()
    {
        $id = $this->getRawOriginal('id');
        if (is_numeric($id)) {
            return $id;
        }
        $domain  = request()->getHost();
        $hashids = new Hashids($domain);
        return data_get($hashids->decode($this->getRawOriginal('movie_key')), '0');
    }

    /**
     * 状态文本
     */
    public function getStateAttribute()
    {
        return data_get(Movie::getStatuses(), $this->status);
    }

    public function getCountSeriesAttribute()
    {
        $playlines = $this->play_lines;
        if ($playlines > 0) {
            $series = array_shift($playlines);
            $data   = data_get($series, 'data', []);
            if (is_array($data)) {
                return count($data);
            }
        }
        return 0;
    }

    public function getIntroductionAttribute()
    {
        $attr = $this->attributes["introduction"] ?? '';
        $str  = preg_replace("/<(\/?span.*?)>/si", "", $attr);
        return $str;
    }

    /**
     * 影片线路(web使用的)
     */
    public function getPlayLinesAttribute()
    {
        return json_decode($this->getRawOriginal('play_lines'), true) ?? [];
    }

    /**
     * 剧集信息(app使用的)
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
        $play_lines = $this->play_lines;
        if (empty($play_lines) || count($play_lines) == 0) {
            return [];
        }
        foreach ($play_lines as $play_line) {
            $series      = [];
            $source_url  = $play_line['url'] ?? null;
            $source_name = $play_line['name'] ?? null;
            $datas       = $play_line['data'] ?? null;
            if (!empty($datas) && count($datas) > 0) {
                foreach ($datas as $data) {
                    $name     = $data['name'];
                    $url      = $data['url'];
                    $series[] = [
                        'name'        => $name,
                        'url'         => $url,
                        'source_name' => $source_name,
                        'source_url'  => $source_url,
                    ];
                }
            }

            $source_names = ['努努资源', '无尽资源', '红牛资源', '天空资源', '百度资源', '快播资源'];

            //根据下面排序优先默认播放线路
            if (array_intersect(array_keys($this->movieSourceNames), $source_names)) {
                if ($source_name == "努努资源" && $series) {
                    break;
                } else if ($source_name == "无尽资源" && $series) {
                    break;
                } else if ($source_name == "红牛资源" && $series) {
                    break;
                } else if ($source_name == "天空资源" && $series) {
                    break;
                } else if ($source_name == "百度资源" && $series) {
                    break;
                } else if ($source_name == "快播资源" && $series) {
                    break;
                }
            } else {
                break;
            }

        }

        // if (empty($data)) {
        //     $name = null;
        //     $url  = null;
        // } else {
        //     $name = $data[0]['name'];
        //     $url  = $data[0]['url'];
        // }

        // $data_series = is_array($this->data) ? $this->data : @json_decode($this->data, true) ?? [];
        // foreach ($data_series as $data_serie) {
        //     $series[] = $data_serie;
        //     //暂时没线路修复逻辑...
        // }

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

    public function getCreatedAtAttribute()
    {
        $createdAt = $this->attributes['created_at'];
        return $createdAt;
    }

    public function getFavoritedAttribute()
    {
        $user = currentUser();
        if ($user) {
            return Favorite::where('favorable_type', 'movies')
                ->where('favorable_id', $this->id)
                ->where('tag', 'favorite')
                ->where('user_id', $user->id)->exists();
        }
        return false;

    }

    //被追剧
    public function getChasedAttribute()
    {
        $user = currentUser();
        if ($user) {
            return Favorite::where('favorable_type', 'movies')
                ->where('favorable_id', $this->id)
                ->where('tag', '!=', 'favorite')
                ->where('user_id', $user->id)->exists();
        }
        return false;
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

//    public function getCountCommentsAttribute()
    //    {
    //        return $this->comments()->count();
    //    }

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

    public function getMovieSourceNamesAttribute()
    {
        $lines = [];
        foreach ($this->play_lines as $play_line) {
            $lines[$play_line['name']] = $play_line['name'];
        }
        return $lines;
    }
}
