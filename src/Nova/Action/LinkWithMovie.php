<?php

namespace Haxibiao\Media\Nova\Action;

use App\Collection as AppCollection;
use App\Movie;
use App\Post;
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
            // $linked_type=array_search(get_class($linkedModel),Relation::$morphMap);
            $linked_type = get_class($linkedModel);
            //morphmap不可用暂时手动判断type
            if ($linkedModel instanceof AppCollection) {
                $linked_type = 'collections';
            } elseif ($linkedModel instanceof Post) {
                $linked_type = 'posts';

            } elseif ($linkedModel instanceof Movie) {
                $linked_type = 'movies';
            }

            $linkedModel->toggleLink($movieIds, $linkedModel->id, $linked_type);
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
