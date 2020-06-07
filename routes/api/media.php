<?php

use Illuminate\Support\Facades\Route;

//请求最新视频
Route::get('/getlatestVideo', 'Api\VideoController@getLatestVideo');

//視頻列表
Route::get('videos', 'Api\VideoController@index'); //旧的api
Route::get('/video/{id}', 'Api\VideoController@show');
Route::get('/video/{id}/fix', 'Api\VideoController@fix'); //修复封面

Route::middleware('auth:api')->post('/video', 'Api\VideoController@store'); //新短视频视频文件上传视频接口
Route::middleware('auth:api')->post('/video/save', 'Api\VideoController@store'); //兼容1.0上传vod视频后回调接口
//获取视频截图
Route::get('/{id}/covers', 'Api\VideoController@covers');

//COS转码后的回调地址
Route::any('/cos/video/hook', 'Api\VideoController@cosHookVideo');

//获取VOD上传签名
Route::get('/signature/vod-{site}', 'Api\VodController@signature');

//图片
Route::get('/image', 'Api\ImageController@index');

//上传图片
Route::post('/image/upload', 'Api\ImageController@upload'); // 兼容哈希表博客和日报？
Route::middleware('auth:api')->post('/image', 'Api\ImageController@store'); //主要上传图片api
Route::middleware('auth:api')->post('/image/save', 'Api\ImageController@store'); //兼容1.0 or vue上传视频接口

//导入接口现在只保留gql的粘贴抖音
// Route::post('/media/import', 'Api\SpiderController@importDouyinSpider');
Route::any('/media/hook', 'Api\SpiderController@hook');
