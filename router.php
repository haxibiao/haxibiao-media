<?php

use Illuminate\Support\Facades\Route;

// APIs routes.
Route::group(
    [
        'middleware' => ['api'],
        'prefix' => 'api'
    ],
    __DIR__ . '/routes/api/media.php'
);

// Web routes.
Route::group(
    [
        'middleware' => ['web'],
    ],
    __DIR__.'/routes/web/media.php'
);