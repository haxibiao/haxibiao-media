<?php

use Illuminate\Support\Facades\Route;

// api routes.
Route::group(
    [
        'middleware' => ['api'],
        'namespace'  => 'Haxibiao\Media\Http\Api',
    ],
    __DIR__ . '/routes/api.php'
);

// web routes.
Route::group(
    [
        'middleware' => ['web'],
        'namespace'  => 'Haxibiao\Media\Http\Controllers',
    ],
    __DIR__ . '/routes/web.php'
);
