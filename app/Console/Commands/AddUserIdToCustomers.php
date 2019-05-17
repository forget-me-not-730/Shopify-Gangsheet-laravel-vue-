<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Console\Command;

class AddUserIdToCustomers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add-user-id-to-customers';

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
        $orders = Order::all();

        foreach ($orders as $key => $order) {
            if ($order->customer_id) {
                Customer::where('id', $order->customer_id)
                    ->update([
                        'user_id' => $order->user_id
                    ]);
            }

            if ($key % 50 === 0) {
                $this->alert("Processed: $key");
            }
        }
    }
}
