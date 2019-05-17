<?php

namespace App\Jobs;

use App\Enums\Queue;
use NZTim\Mailchimp\MailchimpFacade;

class SubscribeToMailchimpList extends BaseJob
{

    /**
     * Create a new job instance.
     */
    public function __construct(private string $email)
    {
        $this->onQueue(Queue::LOW->value);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $listId = config("services.mailchimp.list_id");
        $installed_tag = config("services.mailchimp.installed_tag");
        $uninstalled_tag = config("services.mailchimp.uninstalled_tag");

        try {

            MailchimpFacade::subscribe($listId, $this->email, $merge = [], $confirm = false);

            $subscriberHash = md5(strtolower($this->email));
            MailchimpFacade::api("post", "/lists/{$listId}/members/{$subscriberHash}/tags", ["tags" => [
                ["name" => $installed_tag, "status" => "active"],
                ["name" => $uninstalled_tag, "status" => "inactive"],
            ]]);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
        }
    }
}
