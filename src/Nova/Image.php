<?php

namespace Haxibiao\Media\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Image as NovaImage;
use Laravel\Nova\Resource;

class Image extends Resource
{
    public static $model = 'App\Image';
    public static $title = 'id';

    public static $displayInNavigation = true;
    public static $search              = [
        'id',
    ];

    public static $group = '媒体中心';
    public static function label()
    {
        return '图片';
    }

    public function fields(Request $request)
    {
        return [
            ID::make()->sortable(),
            NovaImage::make('图片', 'path')
                ->store(function (Request $request, $model) {
                    $file = $request->file('path');
                    return $model->saveDownloadImage($file);
                })
                ->thumbnail(function () {
                    return $this->url;
                })->preview(function () {
                return $this->url;
            })->disableDownload(),
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
