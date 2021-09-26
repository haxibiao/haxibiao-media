<?php

namespace Haxibiao\Media\Nova;

use Haxibiao\Media\Nova\Action\DefaultMovieSeries;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Resource;

class MovieSource extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\MovieSource::class;

    public static $group = "媒体中心";

    public static function label()
    {
        return '路线';
    }

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
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
            ID::make(__('ID'), 'id')->sortable(),
            Text::make('名字', 'name'),
            Text::make('备注', 'remark'),
            Text::make('源站链接', 'url'),
            Number::make('权重', 'rank'),
            BelongsTo::make('影片', 'movie', Movie::class)->hideWhenUpdating(),
            Code::make('播放地址', 'play_urls')->json(JSON_PRETTY_PRINT + JSON_UNESCAPED_UNICODE),
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
        return [
            new DefaultMovieSeries(),
        ];
    }
}
