<?php

namespace Haxibiao\Media\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Image;
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

    public static $group = '媒体中心';

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
            BelongsTo::make('电影/电视剧', 'movie', Movie::class)->searchable(),
            Text::make('排序', 'sort'),
            Select::make('类型', 'type')->options([
                1 => '首页',
                2 => '电视剧',
                3 => '电影专题',
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
            }),
            Select::make('状态', 'status')->options([
                1 => '使用中',
                2 => '已禁用',
            ])->resolveUsing(function ($status) {
                if (1 == $status) {
                    return "使用中";
                } else {
                    return "已禁用";
                }
            }),
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
        return [];
    }
}
