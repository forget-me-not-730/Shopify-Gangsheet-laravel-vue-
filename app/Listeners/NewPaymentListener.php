<?php

namespace App\Listeners;

use App\Events\CreditAdded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Spatie\SlackAlerts\Facades\SlackAlert;

class NewPaymentListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CreditAdded $event): void
    {
        try{
            $transaction = $event->transaction;
            $merchant = $transaction->user;
            SlackAlert::to('payment')->blocks([
                [
                    'type'=> 'section',
                    'text' => [
                        "type" => "mrkdwn",
                        "text" => "*Merchant*: <{$merchant->website}|{$merchant->company_name}>"
                    ],
                ],
                [
                    'type'=> 'section',
                    'text' => [
                        "type" => "mrkdwn",
                        "text" => "*Transaction*: <{$transaction->payment_url}|{$transaction->transaction_id}>"
                    ],
                ],
                [
                    'type'=> 'section',
                    'text' => [
                        "type" => "mrkdwn",
                        "text" => "*Amount*: \${$transaction->amount}"
                    ],
                ],
            ]);
        }catch (\Throwable $e){
            report($e);
        }
    }
}
