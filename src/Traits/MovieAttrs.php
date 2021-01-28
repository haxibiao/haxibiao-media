<?php

namespace Haxibiao\Media\Traits;

use App\User;
use Haxibiao\Media\MovieHistory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

trait MovieAttrs
{
    public function getUrlAttribute()
    {
        $path = '/%s/%d';
        $path = sprintf($path, 'movie', $this->id);
        return url($path);
    }

    public function getRegionNameAttribute()
    {
        return $this->region;
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
        }
        $this->attributes['data'] = $value;
    }

    public function getDataAttribute()
    {
        $series       = @json_decode($this->attributes['data']);
        $sortedSeries = array_values(Arr::sort($series, function ($value) {
            return $value->name;
        }));

        //这里不能强制丢异常，很多场景未登录是正常的
        if ($user = getUser(false)) {
            //添加进度记录
            $seriesHistories = MovieHistory::where('user_id', $user->id)
                ->where('movie_id', $this->id)->get();
            foreach ($seriesHistories as $seriesHistory) {
                $index                          = $seriesHistory->series_id;
                $sortedSeries[$index]->progress = $seriesHistory->progress;
            }
        }
        return $sortedSeries;
    }

    public function getCreatedAtAttribute()
    {
        $createdAt = $this->attributes['created_at'];
        return $createdAt;
    }

    public function getFavoritedAttribute()
    {
        //FIXME: 收藏记录数据量50w+之前记得检查index(2 morh columns + user_id column)
        if ($user = getUser(false)) {
            return $this->favorites()->where('user_id', $user->id)->exists();
        }
        return false;
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
