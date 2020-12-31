<?php

namespace Haxibiao\Media\Nova;

use App\Nova\Resource;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;

class SearchLog extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = 'Haxibiao\Media\SearchLog';

    public static $category = "用户内容";

    public static $title = 'name';

    public static function label()
    {
        return "搜索记录";
    }

    public static function singularLabel()
    {
        return "搜索记录";
    }

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'name', 'id',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */

    public function fields(Request $request)
    {
        return [
            ID::make()->sortable(),
            BelongsTo::make('用户', 'user', User::class)->exceptOnForms(),
            Text::make('搜索关键词', 'keyword'),
            Text::make('搜索次数', 'count'),
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
        return [

        ];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function filters(Request $request)
    {
        return [

        ];
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

        ];
    }
}
