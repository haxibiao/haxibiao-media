<?php

namespace Haxibiao\Media\Nova\Action;

use Haxibiao\Media\MovieShowType;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Text;

class AddMovieToShowType extends Action
{
    use InteractsWithQueue, Queueable;
    public $name = '给电影添加展示类型';
    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        $name     = $fields->name;
        $movieIDs = $models->pluck('id');
        $showtype = MovieShowType::firstOrCreate([
            'type_name' => $name,
        ], [
            'type_name' => $name,
            'movie_ids' => $movieIDs,
        ]);
        $showtype->movie_ids = array_unique(array_merge($movieIDs->toArray(), $showtype->movie_ids));
        $showtype->save();
    }

    /**
     * Get the fields available on the action.
     *
     * @return array
     */
    public function fields()
    {
        return [
            Text::make("名字", 'name'),
        ];
    }
}
