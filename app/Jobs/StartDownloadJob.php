<?php

namespace App\Jobs;

use App\Models\DownloadProcess;
use App\Services\Pytube;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StartDownloadJob implements ShouldQueue
{
    use Queueable;

    protected $timeout = 0;

    /**
     * Create a new job instance.
     * 
     * @param int $processId
     * @return void
     */
    public function __construct(
        protected DownloadProcess $process
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $pytube = new Pytube($this->process->url);

        $this->process->update([
            'title' => $pytube->getTitle(),
            'thumbnail_url' => $pytube->getThumbnailUrl(),
        ]);
    }
}
