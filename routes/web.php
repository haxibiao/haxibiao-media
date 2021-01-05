<?php

use Illuminate\Support\Facades\Route;
//电影
Route::middleware(config('media.movie.middleware', []))->prefix('movie')
    ->group(function() {
        Route::get('/riju', 'MovieController@riju');
        Route::get('/meiju', 'MovieController@meiju');
        Route::get('/hanju', 'MovieController@hanju');
        Route::get('/gangju', 'MovieController@gangju');
        Route::get('/qita', 'MovieController@qita');
        Route::get('/search', 'MovieController@search');
        Route::get('/favorites', 'MovieController@favorites');
        Route::resource('/', 'MovieController');
    });

