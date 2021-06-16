<?php

namespace Haxibiao\Media;

use App\User;
use Haxibiao\Breeze\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class Audio extends Model
{

    protected $fillable = [
        'user_id',
        'duration',
        'name',
        'json',
        'hash',
        'disk',
        'path',
    ];

    protected $casts = [
        'json' => 'array',
    ];

    protected $appends = ['url'];

    const QCLOUD_DISK = 'cos';

    //允许扩展类型
    const ALLOW_EXTENSION = ['aac', 'mpga', 'mp3'];

    //最大文件尺寸
    const MAX_FILE_SIZE_BYTE = 10485760;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function isAllowExtension($extension)
    {
        return in_array(strtolower($extension), Audio::ALLOW_EXTENSION);
    }

    public static function isMaxSize($size)
    {
        return $size > Audio::MAX_FILE_SIZE_BYTE;
    }

    public function getUrlAttribute()
    {
        if ($this->disk == Audio::QCLOUD_DISK) {
            return Storage::disk($this->disk)->url($this->path);
        }
    }

    public function getExtensionAttribute()
    {
        return Arr::get($this->json, 'extension');
    }

    public function getDurationMsAttribute()
    {
        $durationsMs = Arr::get($this->json, 'durations_ms', $this->duration * 1000);

        return $durationsMs;
    }

    public function getPlayTimeAttribute()
    {
        return Arr::get($this->json, 'play_time_string', '0:00');
    }
}
