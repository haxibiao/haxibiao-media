<?php

use Illuminate\Support\Facades\Route;

// APIs routes.
Route::group(
    [
        'middleware' => ['api'],
    ],
    __DIR__ . '/routes/api.php'
);