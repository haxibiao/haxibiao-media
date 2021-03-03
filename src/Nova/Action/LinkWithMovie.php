<?php

namespace Haxibiao\Media\Nova\Action;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use OptimistDigital\MultiselectField\Multiselect;

class LinkWithMovie extends Action
{
    use InteractsWithQueue, Queueable;

    public $name = '关联某个电影';
    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        if (empty($models)) {
            Action::danger('请选择要关联长视频的对象列表');
        }
        if (empty($fields->movie_id)) {
            Action::danger('请选择要关联的电影');
        }
        $movieIds = array_values(json_decode($fields->movie_id));
        foreach ($models as $linkedModel) {
            $linkedModel->movie_id = $movieIds[0];
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
            Multiselect::make('选择要关联的电影', 'movie_id')
                ->asyncResource(\App\Nova\Movie::class),
        ];

    }
}
