<?php

namespace App\Listeners;

use App\Mail\WelcomeEmail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Spatie\SlackAlerts\Facades\SlackAlert;

class NewMerchantListener implements ShouldQueue
{
    use InteractsWithQueue;

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
    public function handle(Registered $event): void
    {
        try{
            $merchant = $event->user;

            SlackAlert::to('register')->blocks([
                [
                    'type'=> 'section',
                    'text' => [
                        "type" => "mrkdwn",
                        "text" => "*New Merchant*: ".$merchant->name
                    ],
                ],
                [
                    'type'=> 'section',
                    'text' => [
                        "type" => "mrkdwn",
                        "text" => "*Email*: ".$merchant->email
                    ],
                ],
                [
                    'type'=> 'divider',
                ],
            ]);

            dispatch(new \App\Jobs\SubscribeToMailchimpList($merchant->email));
            Mail::to($merchant->email)->send(new WelcomeEmail($merchant));

        }catch (\Throwable $e){
            report($e);
        }
    }
}
