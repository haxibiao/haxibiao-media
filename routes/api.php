<?php

use Haxibiao\Media\Http\Api\ImageController;
use Haxibiao\Media\Http\Api\SpiderController;
use Haxibiao\Media\Http\Api\VideoController;
use Haxibiao\Media\Http\Api\VodController;
use Illuminate\Support\Facades\Route;
use Illuminate\Contracts\Routing\Registrar as RouteRegisterContract;

/**
 * Video
 */
Route::group(['prefix' => 'api'], function (RouteRegisterContract $api) {
    // Route::post('video/import-video', 'VideoController@importVideo');
    //请求最新视频
    Route::get('/getlatestVideo', VideoController::class . '@getLatestVideo');

    //視頻列表
    Route::get('videos', VideoController::class . '@index'); //旧的api
    Route::get('/video/{id}', VideoController::class . '@show');
    Route::get('/video/{id}/fix', VideoController::class . '@fix'); //修复封面
    Route::get('/video/hash/{hash}', VideoController::class . '@showByVideoHash');

    Route::middleware('auth:api')->post('/video', VideoController::class . '@store'); //新短视频视频文件上传视频接口
    Route::middleware('auth:api')->post('/video/save', VideoController::class . '@store'); //兼容1.0上传vod视频后回调接口
    //获取视频截图
    Route::get('/{id}/covers', VideoController::class . '@covers');

    //COS转码后的回调地址
    Route::any('/cos/video/hook', VideoController::class . '@cosHookVideo');
    //支持上传到vod
    Route::post('video', VideoController::class . '@store');
    //上传到自己服务器
    Route::post('video/upload', VideoController::class . '@store');

    //解析metadata
    Route::post('resolve/video', VideoController::class . '@resolveMetadata');
});

/**
 * Image
 */
Route::group(['prefix' => 'api'], function (RouteRegisterContract $api) {
    //图片
    Route::get('/image', ImageController::class . '@index');
    //上传图片
    Route::post('/image/upload', ImageController::class . '@upload'); // 兼容哈希表博客和日报？
    Route::middleware('auth:api')->post('/image', ImageController::class . '@store'); //主要上传图片api
    Route::middleware('auth:api')->post('/image/save', ImageController::class . '@store'); //兼容1.0 or vue上传视频接口

});

/**
 * Vod
 */
Route::group(['prefix' => 'api'], function (RouteRegisterContract $api) {
    //获取VOD上传签名
    Route::get('/signature/vod-{site}', VodController::class . '@signature');
    Route::get('/signature/vod', VodController::class . '@mySignature');
});

/**
 * Spider
 */
Route::group(['prefix' => 'api'], function (RouteRegisterContract $api) {
    //导入接口现在只保留gql的粘贴抖音
    // Route::post('/media/import', 'SpiderController@importDouyinSpider');
    //media服务抖音采集成功回调
    Route::any('/media/hook', SpiderController::class . '@hook');
});
