<?php

namespace App\Http\Middleware;

use App\Models\DownloadProcess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProcessTokenMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$process = DownloadProcess::where('token', $request->header('Authorization'))->first()) {
            abort(403);
        }

        $request->macro('process', fn () => $process);

        return $next($request);
    }
}
