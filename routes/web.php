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

// 电影
Route::middleware(config('media.movie.middleware', []))
    ->group(function () {
        Route::get('/movie/riju', 'MovieController@riju');
        Route::get('/movie/meiju', 'MovieController@meiju');
        Route::any('/movie/login', 'MovieController@login');
        Route::get('/movie/hanju', 'MovieController@hanju');
        Route::get('/movie/gangju', 'MovieController@gangju');
        Route::get('/movie/qita', 'MovieController@qita');
        Route::get('/movie/search', 'MovieController@search');
        Route::get('/movie/category/{id}', 'MovieController@category');
        Route::get('/movie/favorites', 'MovieController@favorites');
        Route::resource('/movie', 'MovieController');
        Route::get('/movie/list/{pattern}', 'MovieController@movies');
    });
