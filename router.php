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
