<?php

use Illuminate\Support\Facades\Route;

Route::post('start', function () {
    return response()->json([
        'message' => "OK",
    ]);
});
