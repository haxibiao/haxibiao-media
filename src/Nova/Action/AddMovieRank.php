<?php

namespace Haxibiao\Media\Nova\Action;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Number;

class AddMovieRank extends Action
{
    use InteractsWithQueue, Queueable;

    public $name = "添加影片的权重值";

    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        try {
            foreach($models as $model){
                $rank = $fields->rank;
                $model->rank = $rank;
                $model->save();
            }
        } catch (\Exception $exception) {
            Log::error("出错了。。" . $exception->getMessage());
        }
        return Action::message("修改权重成功。。");
    }

    /**
     * Get the fields available on the action.
     *
     * @return array
     */
    public function fields()
    {
        return [
            Number::make('权重值','rank')->min(0)->max(100)->default(1)->help('不能低于0哦'),
        ];
    }
}
