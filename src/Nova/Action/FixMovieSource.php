<?php

namespace Haxibiao\Media\Nova\Action;

use App\MovieSource;
use Haxibiao\Breeze\User;
use Haxibiao\Media\Movie;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Select;

class FixMovieSource extends Action
{
    use InteractsWithQueue, Queueable;
    public $name = '修复线路资源';
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

                $movieSources = $movie->play_lines;
                foreach ($movieSources as $movieSource) {
                    $source = MovieSource::firstOrNew([
                        'name' => $movieSource->name,
                        'url'  => $movieSource->url,
                    ]);
                    $source->movie_id  = $movie->id;
                    $source->rank      = 0;
                    $source->play_urls = $movieSource->data;
                    $source->save();
                }
            }
        }
        return Action::message("修复成功！");

    }

    /**
     * Get the fields available on the action.
     *
     * @return array
     */
    public function fields()
    {
        return [
            Select::make('设为默认播放线路', 'flag')->options([
                1 => '是',
            ])->default(1)->displayUsingLabels(),
        ];
    }
}
