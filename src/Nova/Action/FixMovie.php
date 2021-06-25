<?php

namespace Haxibiao\Media\Nova\Action;

use Haxibiao\Breeze\Notifications\BreezeNotification;
use Haxibiao\Breeze\User;
use Haxibiao\Media\Movie;
use Haxibiao\Media\MovieUser;
use Haxibiao\Media\Traits\MovieRepo;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;

class FixMovie extends Action
{
    use InteractsWithQueue, Queueable;
    public $name = '求片修复';
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
            return Action::danger("每次最多修复一部作品,您选中了{$models->count()}部作品");
        }
        foreach ($models as $model) {
            if ($movie = Movie::find($model->id)) {
                // 求片被修复
                // $movie->status == Movie::ERROR &&
                if ($fields->fixed) {
                    // 发个通知给求片者
                    $users = $movie->findUsers;
                    foreach ($users as $user) {
                        if ($user) {
                            // 通知求片的用户
                            $user->notify(new BreezeNotification(currentUser(), $movie->id, 'movies', '已修复', $movie->cover, $movie->name, '修复了影片'));
                            //还是一个个修改吧，虽然慢了点。。。
                            $user->pivot->update(['report_status' => MovieUser::FIXED]);
                        }
                    }
                }
                $movie->status = $fields->fixed ? Movie::PLAY_FIXED : Movie::ERROR;

                // 获取求片修复提供的 name, url
                $name = $fields->name;
                $url  = $fields->url;
                // 更新 剧集信息
                MovieRepo::updateSeries($movie, $name, $url);
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
        $series = [];
        for ($i = 1; $i <= 110; $i++) {
            $name          = "第${i}集";
            $series[$name] = $name;
        }
        return [
            Select::make('剧集', 'name')
                ->options($series)
                ->withMeta([
                    'value'           => '第1集',
                    'extraAttributes' => [
                        'placeholder' => '第几集...'],
                ]),
            Text::make("播放地址", 'url', )->withMeta(['extraAttributes' => [
                'placeholder' => '目前仅支持m3u8播放地址'],
            ]),
            Select::make("是否完成求片处理", 'fixed')->options([
                0 => '否',
                1 => '是',
            ])
                ->withMeta(['value' => 0])
                ->displayUsingLabels(),
        ];
    }
}
