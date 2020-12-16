<?php

namespace Haxibiao\Media;

use App\Series;
use Haxibiao\Media\Traits\MovieRepo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Haxibiao\Media\Traits\MovieResolvers;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Haxibiao\Media\MovieHistory;

class Movie extends Model
{
    use MovieRepo;
    use MovieResolvers;
    
    protected $guarded = [];

    const MOVIE_RI_JU   = 1;
    const MOVIE_MEI_JU  = 2;
    const MOVIE_HAN_JU  = 3;
    const MOVIE_GANG_JU = 4;

    public $casts = [
        'data' => 'array',
    ];

    public function series(): HasMany
    {
        return $this->hasMany(Series::class);
    }

    public function getCoverUrlAttribute()
    {
        return $this->cover;
    }

    public function favorites()
    {
        return $this->morphMany(\App\Favorite::class, 'faved');
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

    public function activity(): HasOne
    {
        return $this->hasOne(Activity::class);
    }

    public function getFavoritedAttribute()
    {
        if ($user = getUser(false)) {
            return $favorite = $user->favoritedMovie()->where('faved_id', $this->id)->count() > 0;
        }
        return false;
    }

    public function getLastWatchSeriesCache()
    {
        if ($user = getUser()) {
            $history = MovieHistory::where([
                'user_id'  => $user->id,
                'movie_id' => $this->id,
            ])->first();
            return $history->series_id;
        }
    }

    public function getLastWatchProgressCache()
    {
        if ($user = getUser()) {
            $history = MovieHistory::where([
                'user_id'  => $user->id,
                'movie_id' => $this->id,
            ])->first();
            return $history->progress;
        }
    }

}
