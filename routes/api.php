<?php

use App\Http\Controllers\DownloadsController;
use App\Http\Middleware\ProcessTokenMiddleware;
use Illuminate\Support\Facades\Route;

Route::post('download/start', [DownloadsController::class, 'start'])
    ->middleware(ProcessTokenMiddleware::class);
