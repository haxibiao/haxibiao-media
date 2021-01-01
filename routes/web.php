<?php

use Illuminate\Support\Facades\Route;

//电影
Route::get('/movie/riju', 'MovieController@riju');
Route::get('/movie/meiju', 'MovieController@meiju');
Route::get('/movie/hanju', 'MovieController@hanju');
Route::get('/movie/gangju', 'MovieController@gangju');
Route::get('/movie/qita', 'MovieController@qita');
Route::get('/movie/favorites', 'MovieController@favorites');

Route::get('/movie/search', 'MovieController@search');
Route::resource('/movie', 'MovieController');
