<?php

namespace Haxibiao\Media\Nova;

use Haxibiao\Content\Nova\Post;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Resource;

class Movie extends Resource
{
    public static $group = "媒体中心";
    public static function label()
    {
        return '电影';
    }

    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = 'Haxibiao\Media\Movie';

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
        'id', 'name',
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
            Text::make('电影名', 'name')->hideWhenCreating(),
            Text::make('地区', 'region')->hideWhenCreating(),
            Text::make('年份', 'year')->hideWhenCreating(),
            Text::make('分类', 'type')->hideWhenCreating(),
            Text::make('风格', 'style')->hideWhenCreating(),
            HasMany::make('关联动态', 'posts', Post::class),
            Select::make('状态', 'status')->options([
                1  => '公开',
                0  => '草稿',
                -1 => '下架',
            ])->displayUsingLabels(),
            // Text::make('添加时间', 'created_at')->sortable()->onlyOnIndex(),
            Image::make('封面', 'movie.cover')->thumbnail(
                function () {
                    return $this->cover;
                }
            )->preview(
                function () {
                    return $this->cover;
                }
            ),
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
        ];
    }
}
