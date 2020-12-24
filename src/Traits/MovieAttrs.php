<?php

namespace Haxibiao\Media\Traits;

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
        $series = @json_decode($this->attributes['data']);
        return array_values(array_sort($series, function ($value) {
            return $value->name;
        }));

    }

    public function activity(): HasOne
    {
        return $this->hasOne(Activity::class);
    }

    public function getFavoritedAttribute()
    {
        if ($user = getUser(false)) {
            if (in_array(config('app.name'), ['datizhuanqian'])) {
                return $favorite = $user->favoritedMovie()->where('favorable_id', $this->id)->count() > 0;
            } else {
                return $favorite = $user->favorites()->where('faved_type', 'movies')->where('faved_id', $this->id)->count() > 0;
            }
        }
        return false;
    }

    // public function getLastWatchSeriesAttribute()
    // {
    //     if (checkUser()) {
    //         $user=getUser();
    //         $history = MovieHistory::where([
    //             'user_id'  => $user->id,
    //             'movie_id' => $this->id,
    //         ])->latest()->first();
    //         return $history->series_id;
    //     }
    // }

    // public function getLastWatchProgressAttribute()
    // {
    //     if (checkUser()) {
    //         $user=getUser();
    //         $history = MovieHistory::where([
    //             'user_id'  => $user->id,
    //             'movie_id' => $this->id,
    //         ])->latest()->first();
    //         return $history->progress;
    //     }
    // }

    public function getCountFavoritesAttribute()
    {
        return $this->favorites()->count();
    }

    public function getCountCommentsAttribute()
    {
        return $this->comments()->count();
    }
}
