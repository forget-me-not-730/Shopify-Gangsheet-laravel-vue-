<?php

namespace App\Jobs;

use App\Enums\Queue;
use Illuminate\Support\Facades\Storage;

class UploadFileJob extends BaseJob
{

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly string $localPath, private readonly string $remotePath)
    {
        $this->onQueue(Queue::HIGH->value);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Storage::disk('spaces')->put($this->remotePath, file_get_contents($this->localPath));
        unlink($this->localPath);
    }
}
