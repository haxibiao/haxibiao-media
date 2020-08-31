<?php

use Haxibiao\Media\Http\Api\ImageController;
use Haxibiao\Media\Http\Api\VideoController;
use Illuminate\Support\Facades\Route;

//多媒体
Route::resource('/image', ImageController::class );
Route::get('/video/list', VideoController::class .'@list');
Route::get('/video/{id}/process', VideoController::class . '@processVideo');
Route::resource('/video', VideoController::class);
