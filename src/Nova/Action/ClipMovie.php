<?php

namespace Haxibiao\Media\Nova\Action;

use App\Video;
use Haxibiao\Media\Traits\MovieRepo;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;

class ClipMovie extends Action
{
    use InteractsWithQueue, Queueable;
    public $name = '剪辑影片';
    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        $user = Auth::user();
        if ($models->count() > 1) {
            return Action::danger("每次最多剪辑一部作品,您选中了{$models->count()}部作品");
        }
        $movie = $models->first();
        // 获取剪辑目标
        $targetM3u8 = self::findTargetM3u8($fields->name, $movie);
        // 获取新m3u8
        $newM3u8 = MovieRepo::clipM3u8($targetM3u8, $fields->startTime, $fields->endTime);
        // 文件名 = source_key + 当前时间戳.m3u8
        $filename    = $movie->source_key . '-' . time() . ".m3u8";
        $cdn         = rand_pick_ucdn_domain();
        $newM3u8Path = '/clip/' . $filename;
        // 影片剪辑都存储到 storage(local|cloud)
        $playUrl = Storage::url("m3u8/clip/" . env('APP_NAME') . "/{$newM3u8Path}");
        Storage::put($newM3u8Path, $newM3u8, 'public');
        // 计算视频时长
        preg_match_all('/\d+[.]\d+/', $newM3u8, $arr);
        $duration = array_sum($arr[0]);
        $duration = (int) $duration;
        // 存储成视频
        $video = Video::create([
            'user_id'  => $user->id,
            'duration' => $duration,
            'disk'     => config('filesystems.cloud'),
            'path'     => $playUrl,
        ]);
    }

    /**
     * 根据集名字获取目标m3u8地址
     */
    public static function findTargetM3u8($name, $movie)
    {
        $series = $movie->data;
        foreach ($series as $item) {
            if ($item->name == $name) {
                return $item->url;
            }
        }
    }

    /**
     * Get the fields available on the action.
     *
     * @return array
     */
    public function fields()
    {
        return [
            Text::make("剪辑对象（第几集）", 'name'),
            Text::make("短视频标题", 'postTitle'),
            Number::make('开始时间（秒）', 'startTime'),
            Number::make('结束时间（秒）', 'endTime'),
        ];
    }
}
