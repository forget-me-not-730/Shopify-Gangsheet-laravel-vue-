<?php

namespace App\Jobs;

use App\Enums\Queue;
use App\GangSheet\GangSheet;
use App\Models\Design;

class OutputGangSheet extends BaseJob
{
    public int $tries = 2;

    public int $timeout = 1200;

    public int $retryAfter = 1230;

    public bool $failOnTimeout = true;

    private string|null $method;

    /**
     * Create a new job instance.
     */
    public function __construct(private $design_id, $options = [])
    {
        $this->afterCommit();

        $queue = $options['queue'] ?? Queue::GANG_SHEET->value;
        $this->onQueue($queue);

        $this->method = $options['method'] ?? null;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $design = Design::withTrashed()->find($this->design_id);

        $design->addLog("Gang sheet generation started");
        $design->getDesignJson();

        GangSheet::create($this->design_id)
            ->generate($this->method);
    }

    public function failed($exception): void
    {
        $design = Design::withTrashed()->find($this->design_id);

        if (!$design->isCompleted()) {
            $design->update([
                'status' => Design::STATUS_FAILED
            ]);

            $design->addLog("Failed to generate gang sheet on Error: {$exception->getMessage()}");
        }
    }
}
