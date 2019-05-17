<?php

namespace App\Jobs;

use App\Enums\Queue;
use App\Models\User;
use App\Models\UserImage;

class RegenerateWatermarkImages extends BaseJob
{

    public $tries = 0;

    public int $retryAfter = 36000;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly string $user_id)
    {
        $this->onQueue(Queue::LOW->value);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = User::find($this->user_id);

        try {
            $userImages = UserImage::with('user')
                ->where('user_id', $this->user_id)
                ->get();

            $user->setWatermarkProcessing(true);
            $processed = 0;
            foreach ($userImages as $image) {
                try {
                    $image->generateWatermarkImage();
                    $processed++;
                } catch (\Exception $exception) {
                    info($exception->getMessage());
                }
                $user->setSetting([
                    'processed' => $processed,
                ]);
            }
            $user->setWatermarkProcessing(false);
            $user->setSetting([
                'has_watermark' => true,
                'has_watermark_preview' => true
            ]);
        } catch (\Exception $exception) {
            $user->setWatermarkProcessing(false);
            report($exception);
        }
    }
}
