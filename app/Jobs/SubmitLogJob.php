<?php

namespace App\Jobs;

use App\Enums\Queue;

class SubmitLogJob extends BaseJob
{
    public function __construct(private readonly string $path, private readonly string $content)
    {
        $this->onQueue(Queue::LOG->value);
    }

    public function handle(): void
    {
        spaces()->prepend($this->path, $this->content);
    }
}
