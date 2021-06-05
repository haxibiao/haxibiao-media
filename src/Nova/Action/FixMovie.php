<?php

namespace Haxibiao\Media\Nova\Action;

use Haxibiao\Media\Movie;
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
        $movie = $models->first();

        //求片已解决
        if ($fields->fixed) {
            $movie->status = Movie::ERROR;
        }

        // 获取求片修复提供的 name, url
        $name = $fields->name;
        $url  = $fields->url;
        // 更新 剧集信息
        MovieRepo::updateSeries($movie, $name, $url);
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
                ->withMeta(['value' => '1'])
                ->displayUsingLabels(),
        ];
    }
}
