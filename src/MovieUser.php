<?php

namespace Haxibiao\Media;

use Haxibiao\Breeze\Model;
use Haxibiao\Breeze\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovieUser extends Model
{

    protected $guarded = [];

    protected $table = 'movie_user';

    public const PROCESSING = 0; // 求片中
    public const UPDATED    = 1; // 已更新
    public const FIXED      = 2; // 已修复

    public static function getStatus()
    {
        return [
            MovieUser::PROCESSING => "求片中",
            MovieUser::UPDATED    => "已更新",
            MovieUser::FIXED      => "已修复",
        ];
    }

    public function user()
    {
        return $this->belongsTo(\App\User::class);
    }

    public function movie()
    {
        return $this->belongsTo(\App\Movie::class);
    }

}
