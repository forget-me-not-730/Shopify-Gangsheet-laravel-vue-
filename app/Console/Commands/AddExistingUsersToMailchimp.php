<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class AddExistingUsersToMailchimp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add-existing-users-to-mailchimp';

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
        $users = User::select(["id", "deleted_at", "email", "name"])
            ->withTrashed()
            ->get();

        $this->alert("Total Users:" . $users->count());
        $subscribed_count = 0;
        $unsubscribed_count = 0;

        foreach ($users as $key => $user) {
            if (isBagsEmail($user->email)) {
                continue;
            }
            if ($user->deleted_at) {
                dispatch_sync(new \App\Jobs\UnSubscribeFromMailchimpList($user->email));
                $unsubscribed_count++;
            } else {
                dispatch_sync(new \App\Jobs\SubscribeToMailchimpList($user->email));
                $subscribed_count++;
            }

            if ($key % 10 == 0) {
                $this->alert("Processed:" . $key);
            }

            sleep(0.5);
        }
        $this->alert("Total Subscribed Users:" . $subscribed_count);
        $this->alert("Total UnSubscribed Users:" . $unsubscribed_count);
    }
}
