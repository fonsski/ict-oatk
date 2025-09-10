<?php

namespace App\Providers;

use App\Events\TicketCreated;
use App\Events\TicketStatusChanged;
use App\Events\TicketAssigned;
use App\Events\UserCreated;
use App\Events\UserStatusChanged;
use App\Listeners\LogTicketCreated;
use App\Listeners\LogTicketStatusChanged;
use App\Listeners\LogTicketAssigned;
use App\Listeners\LogUserCreated;
use App\Listeners\LogUserStatusChanged;
use App\Listeners\WebSocketNotificationListener;
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
        ],

        // События заявок
        TicketCreated::class => [
            LogTicketCreated::class,
            WebSocketNotificationListener::class,
        ],

        TicketStatusChanged::class => [
            LogTicketStatusChanged::class,
            WebSocketNotificationListener::class,
        ],

        TicketAssigned::class => [
            LogTicketAssigned::class,
            WebSocketNotificationListener::class,
        ],

        // События пользователей
        UserCreated::class => [
            LogUserCreated::class,
        ],

        UserStatusChanged::class => [
            LogUserStatusChanged::class,
        ],
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
