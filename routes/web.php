<?php

use Illuminate\Support\Facades\Route;

// 图片
Route::resource('/image', 'ImageController');

// 视频
Route::middleware(config('media.video.middleware', []))->group(function () {
    Route::get('/video/list', 'VideoController@list');
    Route::get('/video/{id}', 'VideoController@show');
    Route::get('/video/{id}/process', 'VideoController@processVideo');
    Route::resource('/video', 'VideoController');
});

// 长视频
Route::middleware(config('media.movie.middleware', []))
    ->group(function () {
        Route::any('/movie/login', 'MovieController@login');

        Route::get('/movie/dongman', 'MovieController@dongman');
        Route::get('/movie/dianshiju', 'MovieController@dianshiju');
        Route::get('/movie/zongyi', 'MovieController@zongyi');
        Route::get('/movie/dianying', 'MovieController@dianying');
        Route::get('/movie/lunli', 'MovieController@lunli');

        Route::get('/movie/riju', 'MovieController@riju');
        Route::get('/movie/meiju', 'MovieController@meiju');
        Route::get('/movie/hanju', 'MovieController@hanju');
        Route::get('/movie/gangju', 'MovieController@gangju');

        Route::get('/movie/qita', 'MovieController@qita');

        Route::get('/movie/search', 'MovieController@search');
        Route::get('/movie/category/{id}', 'MovieController@category');
        Route::get('/movie/favorites', 'MovieController@favorites');

        Route::get('/movie/list/{pattern}', 'MovieController@list');

        Route::resource('/movie', 'MovieController');
    });
