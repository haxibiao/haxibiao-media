<?php

use Illuminate\Support\Facades\Route;
//电影
Route::middleware(config('media.movie.middleware', []))
    ->group(function () {
        Route::get('/movie/riju', 'MovieController@riju');
        Route::get('/movie/meiju', 'MovieController@meiju');
        Route::get('/movie/hanju', 'MovieController@hanju');
        Route::get('/movie/gangju', 'MovieController@gangju');
        Route::get('/movie/qita', 'MovieController@qita');
        Route::get('/movie/search', 'MovieController@search');
		/**
		 * 下面这个路由与content package冲突，在此做特殊处理：
		 * 	参考：\Haxibiao\Media\Movie@getCategories() 方法。
		 *  ID范围在[1,11]才会进入下条路由。
		 */
        Route::get('/category/{id}', 'MovieController@category')
			->where('id','^[1-11]{1,2}$');
        Route::get('/movie/favorites', 'MovieController@favorites');
        Route::resource('/movie', 'MovieController');
    });
