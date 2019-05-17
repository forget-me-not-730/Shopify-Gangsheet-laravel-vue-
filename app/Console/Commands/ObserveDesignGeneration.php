<?php

namespace App\Console\Commands;

use App\Enums\Queue;
use App\Jobs\OutputGangSheet;
use App\Models\Design;
use Illuminate\Console\Command;
use Spatie\SlackAlerts\Facades\SlackAlert;

class ObserveDesignGeneration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'design:observe';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $designs = Design::withTrashed()
            ->whereIn('status', [Design::STATUS_FAILED, Design::STATUS_PROCESSING, Design::STATUS_PENDING])
            ->with(['metaData'])
            ->get();

        $sendSlackAlert = false;

        $blocks[] = [
            'type' => 'divider',
        ];

        $blocks = [
            [
                'type' => 'section',
                'text' => [
                    "type" => "mrkdwn",
                    "text" => "Hey, <@U05FFFSCUA0> <@U05FFFPT91S>"
                ],
            ],
            [
                'type' => 'section',
                'text' => [
                    "type" => "mrkdwn",
                    "text" => "*Platform*: Bags"
                ],
            ],
        ];

        $notifiedDesigns = option('notified_designs', []);
        $newNotifyDesigns = [];

        if ($designs->count() > 20) {
            $sendSlackAlert = true;

            $blocks[] = [
                'type' => 'section',
                'text' => [
                    "type" => "mrkdwn",
                    "text" => "There are more than {$designs->count()} designs processing in the queue"
                ],
            ];
        }

        $failedDesigns = $designs->where('status', Design::STATUS_FAILED);

        if ($failedDesigns->count() > 0) {
            foreach ($failedDesigns as $design) {
                if (!in_array($design->id, $notifiedDesigns)) {
                    $sendSlackAlert = true;
                    $blocks[] = [
                        'type' => 'section',
                        'text' => [
                            "type" => "mrkdwn",
                            "text" => "Design *{$design->id}* failed to generate"
                        ],
                    ];

                    OutputGangSheet::dispatch($design->id, [
                        'queue' => Queue::GANG_SHEET_THREE->value
                    ]);
                }

                $newNotifyDesigns[] = $design->id;
            }
        }

        option(['notified_designs' => $newNotifyDesigns]);

        if ($sendSlackAlert) {
            SlackAlert::to('generation')->blocks($blocks);
        }
    }
}
