<?php

namespace Haxibiao\Media\Nova;

use Haxibiao\Breeze\Nova\User;
use Haxibiao\Content\Nova\Filters\MoviesByRegion;
use Haxibiao\Content\Nova\Filters\MoviesByStatus;
use Haxibiao\Content\Nova\Filters\MoviesByStyle;
use Haxibiao\Content\Nova\Filters\MoviesByType;
use Haxibiao\Content\Nova\Post;
use Haxibiao\Media\Nova\Action\FixMovie;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
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
    public static $model = \Haxibiao\Media\Movie::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

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
            Text::make('电影名', 'name'),
            Textarea::make('简介', 'introduction'),
            Text::make('地区', 'region'),
            Text::make('年份', 'year'),
            Text::make('导演', 'producer'),
            Text::make('演员', 'actors')->hideFromIndex(),
            Text::make('分类', 'type'),
            Text::make('风格', 'style'),
            BelongsTo::make('求片者', 'user', User::class)->hideWhenCreating()->hideWhenUpdating(),
            Select::make('状态', 'status')->options(\Haxibiao\Media\Movie::getStatuses())->displayUsingLabels(),
            Image::make('封面', 'cover')->thumbnail(function () {
                return $this->cover;
            })->preview(function () {
                return $this->cover;
            })->store(function (Request $request, \Haxibiao\Media\Movie $model) {
                $file = $request->file('cover');
                return $model->saveCover($file);
            }),

            Code::make('剧集', 'data')->json(JSON_PRETTY_PRINT + JSON_UNESCAPED_UNICODE)->rules('required', 'max:4000'),

            HasMany::make('关联动态', 'posts', Post::class),
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

        return [
            new MoviesByStatus,
            new MoviesByRegion,
            new MoviesByType,
            new MoviesByStyle,
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
            new FixMovie,
        ];
    }
}
