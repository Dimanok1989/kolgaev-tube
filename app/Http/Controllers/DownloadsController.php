<?php

namespace App\Http\Controllers;

use App\Jobs\StartDownloadJob;
use Illuminate\Http\Request;

class DownloadsController extends Controller
{
    /**
     * Запуск скачивания видео
     * 
     * @param \Illuminate\Http\Request $request
     * @return 
     */
    public function start(Request $request)
    {
        StartDownloadJob::dispatch(
            $request->process()
        );

        return response()->json([
            'message' => "OK"
        ]);
    }
}
