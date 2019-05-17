<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Spatie\SlackAlerts\Facades\SlackAlert;

class NewOrderListener
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
    public function handle(OrderCreated $event): void
    {
        try{
            $order = $event->order;
            $merchant = $order->user;
            $product = $order->product;
            $items = [];
            foreach ($order->designs as $design){
                $items[] = [
                    'type'=> 'section',
                    'text' => [
                        "type" => "mrkdwn",
                        "text" => "<{$design->watermark_url}|{$design->size->label}>"
                    ],
                ];
            }
            SlackAlert::to('order')->blocks([
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
                        "text" => "*Product*: <{$product->redirect_url}|{$product->title}>"
                    ],
                ],
                [
                    'type'=> 'section',
                    'text' => [
                        "type" => "mrkdwn",
                        "text" => "*Customer*: {$order->name}, {$order->email}"
                    ],
                ],
                ...$items,
                [
                    'type'=> 'divider',
                ],
            ]);
        }catch (\Throwable $e){
            report($e);
        }
    }
}
