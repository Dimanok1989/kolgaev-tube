<?php

use App\Http\Controllers\DownloadsController;
use App\Http\Middleware\ProcessTokenMiddleware;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    abort(404);
    return view('welcome');
});

Route::get('youtube/{folder}/{file}', [DownloadsController::class, 'download'])
    ->middleware(ProcessTokenMiddleware::class);
