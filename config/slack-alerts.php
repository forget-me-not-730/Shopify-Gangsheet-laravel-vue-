<?php

return [
    /*
     * The webhook URLs that we'll use to send a message to Slack.
     */
    'webhook_urls' => [
        'default'  => env('SLACK_ALERT_WEBHOOK_REPORT'),
        'register' => env('SLACK_ALERT_WEBHOOK_REGISTER'),
        'payment'  => env('SLACK_ALERT_WEBHOOK_PAYMENT'),
        'order'    => env('SLACK_ALERT_WEBHOOK_ORDER'),
        'generation'    => env('SLACK_ALERT_WEBHOOK_GENERATION'),
    ],

    /*
     * This job will send the message to Slack. You can extend this
     * job to set timeouts, retries, etc...
     */
    'job'          => App\Jobs\SendSlackAlertJob::class,
];
