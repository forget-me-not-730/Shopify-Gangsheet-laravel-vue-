<?php

namespace App\Providers;

use App\Events\CreditAdded;
use App\Events\OrderCreated;
use App\Listeners\NewMerchantListener;
use App\Listeners\NewOrderListener;
use App\Listeners\NewPaymentListener;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
            NewMerchantListener::class
        ],
        OrderCreated::class => [
            NewOrderListener::class
        ],
        CreditAdded::class => [
            NewPaymentListener::class
        ]
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
