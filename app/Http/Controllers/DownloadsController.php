<?php

namespace App\Http\Controllers;

use App\Jobs\DeleteFileJob;
use App\Jobs\StartDownloadJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DownloadsController extends Controller
{
    /**
     * Запуск скачивания видео
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
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

    /**
     * Скачивание файла
     * 
     * @param \Illuminate\Http\Request $request
     * @param string $folder
     * @param string $file
     * @return \Illuminate\Http\Response
     */
    public function download(Request $request, string $folder, string $file)
    {
        $path = collect(['youtube', $folder, urldecode($file)])->join(DIRECTORY_SEPARATOR);

        $storage = Storage::disk('local');
        abort_if(!$storage->exists($path), 404);

        return response()->file(
            $storage->path($path)
        );
    }

    /**
     * Удаление файлов
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {
        DeleteFileJob::dispatch(
            $request->process()
        );

        return response()->json([
            'message' => "OK"
        ]);
    }
}
