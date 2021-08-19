<?php

namespace Haxibiao\Media\Nova;

use App\Activity as AppActivity;
use App\Nova\Collection;
use App\Nova\Movie;
use Haxibiao\Content\Nova\Actions\AddActivitiesToSticks;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\MorphTo;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Resource;

class Activity extends Resource
{
    public static $model  = \App\Activity::class;
    public static $title  = 'name';
    public static $search = [
        'title',
    ];

    public static $group = '小编精选';

    public static function label()
    {
        return '轮播图';
    }

    public function fields(Request $request)
    {
        return [
            ID::make(__('ID'), 'id')->sortable(),
            Text::make('标题', 'title'),
            Text::make('副标题', 'subtitle'),
            // BelongsTo::make('电影/电视剧', 'movie', Movie::class)->searchable(),
            // BelongsTo::make('定制电影', 'movie', Movie::class)->nullable()->searchable(),
            // BelongsTo::make('定制合集', 'collection', Collection::class)->nullable()->searchable(),

            MorphTo::make('指定对象', 'activityable')->types([
                Movie::class,
                Collection::class,
            ])->searchable(),
            Text::make('排序', 'sort'),
            Select::make('类型', 'type')->options([
                AppActivity::TYPE_INDEX   => '首页',
                AppActivity::TYPE_SERIE   => '电视剧',
                AppActivity::TYPE_PROJECT => '电影专题',
                AppActivity::TYPE_SEARCH  => '搜索'
            ])->resolveUsing(function ($type) {
                if (1 == $type) {
                    return "首页";
                }
                if (2 == $type) {
                    return "电视剧";
                }
                if (3 == $type) {
                    return "电影专题";
                }
                if( 4 == $type){
                    return '搜索词';
                }
            })->withMeta(['value' => 1]),
            Select::make('状态', 'status')->options([
                1 => '使用中',
                2 => '已禁用',
            ])->resolveUsing(function ($status) {
                if (1 == $status) {
                    return "使用中";
                } else {
                    return "已禁用";
                }
            })->withMeta(['value' => 1]),
            Image::make('图片地址', 'image_url')->thumbnail(function () {
                return $this->image_url;
            })->preview(function () {
                return $this->image_url;
            })->store(function (Request $request, \Haxibiao\Media\Activity $model) {
                $file = $request->file('image_url');
                return $model->saveActivityImage($file);
            }),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function cards(Request $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function filters(Request $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function lenses(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function actions(Request $request)
    {
        return [
            new AddActivitiesToSticks,
        ];
    }
}
