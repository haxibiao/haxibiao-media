<?php

namespace Haxibiao\Media\Nova;

use App\Nova\Resource;
use Haxibiao\Breeze\Nova\User;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;

class SearchLog extends Resource
{
    public static $model = 'Haxibiao\Media\SearchLog';
    public static $title = 'name';

    public static $group = "数据中心";
    public static function label()
    {
        return "搜索记录";
    }

    public static $search = [
        'id','keyword'
    ];

    public function fields(Request $request)
    {
        return [
            ID::make()->sortable(),
            BelongsTo::make('用户', 'user', User::class)->exceptOnForms(),
            Text::make('搜索关键词', 'keyword'),
            Text::make('搜索次数', 'count')->sortable(),
            DateTime::make('创建时间', 'created_at'),
            DateTime::make('更新时间', 'updated_at'),
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
