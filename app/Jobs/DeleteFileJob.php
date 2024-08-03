<?php

namespace App\Jobs;

use App\Models\DownloadProcess;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class DeleteFileJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     * 
     * @param \App\Models\DownloadProcess $process
     * @return void
     */
    public function __construct(
        protected DownloadProcess $process
    ) {
        //
    }

    /**
     * Execute the job.
     * 
     * @return void
     */
    public function handle(): void
    {
        $dir = "youtube/{$this->process->process_id}";
        
        if (Storage::directoryExists($dir)) {
            Storage::deleteDirectory($dir);
        }
    }
}
