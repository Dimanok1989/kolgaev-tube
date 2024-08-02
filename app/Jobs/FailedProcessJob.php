<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class FailedProcessJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected string $baseUrl,
        protected string $errorMessage,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Http::post($this->baseUrl . "/fail", [
            'message' => $this->errorMessage
        ]);
    }
}
