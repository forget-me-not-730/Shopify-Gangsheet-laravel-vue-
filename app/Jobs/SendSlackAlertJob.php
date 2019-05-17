<?php

namespace App\Jobs;

use App\Enums\Queue;
use Spatie\SlackAlerts\Jobs\SendToSlackChannelJob;

class SendSlackAlertJob extends SendToSlackChannelJob
{
    public function __construct(string $webhookUrl, ?string $text = null, ?array $blocks = null, ?string $channel = null)
    {
        parent::__construct($webhookUrl, $text, $blocks, $channel);
        $this->onQueue(Queue::LOW->value);
    }


    public function handle(): void
    {
        try {
            parent::handle();
        } catch (\Exception $exception) {
            info($exception->getMessage());
        }
    }
}
