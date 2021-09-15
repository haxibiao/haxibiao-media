<?php

namespace Haxibiao\Media\Nova\Action;

use Haxibiao\Media\Movie;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Select;
use TuneZilla\DynamicActionFields\DynamicFieldAction;

class DefaultMovieSource extends Action
{
    use DynamicFieldAction;

    use InteractsWithQueue, Queueable;
    public $name = '设置默认播放线路';
    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        $movie_source = $fields->movie_source;
        if ($models->count() > 1) {
            return Action::danger("每次最多修复一部作品,您选中了{$models->count()}部作品");
        }
        foreach ($models as $model) {
            if ($movie = Movie::find($model->id)) {
                foreach ($model->play_lines as $play_line) {
                    if ($play_line->name == $movie_source) {
                        $movie->update(['data' => $play_line->data]);
                    }
                }
            }
        }
        return Action::message("设置-{$movie_source}-为默认线路成功！");
    }

    //需要引入tunezilla/dynamic-action-fields
    public function fieldsForModels(Collection $models): array
    {
        if ($models->isEmpty()) {
            return [];
        }

        $movie = $models->first();
        return [
            Select::make('设为默认播放线路', 'movie_source')->options(
                $movie->movieSourceNames,
            )->displayUsingLabels(),
        ];
    }
}
