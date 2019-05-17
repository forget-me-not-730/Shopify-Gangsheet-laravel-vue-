<?php

namespace App\Jobs;

use App\Enums\Queue;
use App\Models\UserImage;

class GenerateWatermarkImage extends BaseJob
{

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly array $image_ids)
    {
        $this->afterCommit();
        $this->onQueue(Queue::DEFAULT->value);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        foreach ($this->image_ids as $image_id) {
            try {
                $userImage = UserImage::find($image_id);
                $userImage?->generateWatermarkImage();
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
