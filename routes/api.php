<?php

use Illuminate\Support\Facades\Route;

/**
 * Video
 */

//网页短视频首页 加载更多
Route::get('/getlatestVideo', 'VideoController@getLatestVideo');
//視頻列表
Route::get('videos', 'VideoController@index'); //旧的api
Route::get('/video/{id}', 'VideoController@show');
Route::get('/video/{id}/fix', 'VideoController@fix'); //修复封面
Route::get('/video/hash/{hash}', 'VideoController@showByVideoHash');
Route::middleware('auth:api')->post('/video', 'VideoController@store'); //新短视频视频文件上传视频接口
Route::middleware('auth:api')->post('/video/save', 'VideoController@store'); //兼容1.0上传vod视频后回调接口
//获取视频截图
Route::get('/{id}/covers', 'VideoController@covers');
//COS转码后的回调地址
Route::any('/cos/video/hook', 'VideoController@cosHookVideo');
//支持上传到vod
Route::post('video', 'VideoController@store');
//上传到自己服务器
Route::post('video/upload', 'VideoController@store');
//解析metadata
Route::post('resolve/video', 'VideoController@resolveMetadata');

//电影api
Route::group(['prefix' => 'movie'], function ($api) {
    Route::any('/clip', 'MovieController@clip');
    Route::any('/update_video_cover', 'MovieController@updateClipVideoCover');
    Route::post('/danmu/v3', 'MovieController@sendDanmu');
    Route::get('/danmu/v3', 'MovieController@danmu');
    Route::get('/{id}/comment', 'MovieController@getComment');
    Route::post('/comment/store', 'MovieController@comment');
    Route::any('/toggle-like', 'MovieController@toggoleLike');
    Route::any('/toggle-fan', 'MovieController@toggoleFan');
    Route::any('/save-watch_progress', 'MovieController@saveWatchProgress');
    Route::any('/get-watch_progress', 'MovieController@getWatchProgress');
    Route::any('/report', 'MovieController@report');
    Route::any('/history', 'MovieController@movieHistory');
    //电影剧集播放器
    Route::get('/{movie}/series', 'MovieController@getSeries');
    //rest api
    Route::any('/list', 'MovieController@index');
    Route::any('/json/{movie}', 'MovieController@show');

});

/**
 * Image
 */

//图片
Route::get('/image', 'ImageController@index');
//上传图片
Route::post('/image/upload', 'ImageController@upload'); // 兼容哈希表博客和日报？
Route::middleware('auth:api')->post('/image', 'ImageController@store'); //主要上传图片api
Route::middleware('auth:api')->post('/image/save', 'ImageController@store'); //兼容1.0 or vue上传视频接口

/**
 * FIXME: Vod签名获取是统一的，不同站点的唯一差异是classid, 需要重构到哈希云去
 */

//获取VOD上传签名
/**
 * @deprecated 已废用，请使用哈希云获取VOD签名
 */
Route::get('/signature/vod-{site}', 'VodController@signature');
/**
 * @deprecated 已废用，请使用哈希云获取VOD签名
 */
Route::get('/signature/vod', 'VodController@mySignature');

/**
 * Spider
 */

//导入接口处理粘贴的抖音？
Route::post('/douyin/import', 'SpiderController@importDouYin');
Route::post('/media/import', 'SpiderController@importDouyinSpider');

Route::any('/media/oldHook', 'HookController@hookSpider');

//抖音采集成功回调
Route::any('/media/hook', 'HookController@hookSpider');
//上传vod视频的回调
Route::any('/video/hook', 'HookController@hookVideo');
