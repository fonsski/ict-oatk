<?php

namespace App\Providers;

use App\Events\TicketCreated;
use App\Events\TicketStatusChanged;
use App\Events\TicketAssigned;
use App\Events\TicketCommentCreated;
use App\Events\UserCreated;
use App\Events\UserStatusChanged;
use App\Events\EquipmentStatusChanged;
use App\Events\EquipmentLocationChanged;
use App\Events\KnowledgeBaseArticleCreated;
use App\Events\KnowledgeBaseArticleUpdated;
use App\Events\SystemNotificationCreated;
use App\Listeners\LogTicketCreated;
use App\Listeners\LogTicketStatusChanged;
use App\Listeners\LogTicketAssigned;
use App\Listeners\LogTicketCommentCreated;
use App\Listeners\LogUserCreated;
use App\Listeners\LogUserStatusChanged;
use App\Listeners\LogEquipmentStatusChanged;
use App\Listeners\LogEquipmentLocationChanged;
use App\Listeners\LogKnowledgeBaseArticleCreated;
use App\Listeners\LogKnowledgeBaseArticleUpdated;
use App\Listeners\LogSystemNotificationCreated;
use App\Listeners\WebSocketNotificationListener;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>

    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        
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

        TicketCommentCreated::class => [
            LogTicketCommentCreated::class,
            WebSocketNotificationListener::class,
        ],

        
        UserCreated::class => [
            LogUserCreated::class,
            WebSocketNotificationListener::class,
        ],

        UserStatusChanged::class => [
            LogUserStatusChanged::class,
            WebSocketNotificationListener::class,
        ],

        
        EquipmentStatusChanged::class => [
            LogEquipmentStatusChanged::class,
            WebSocketNotificationListener::class,
        ],

        EquipmentLocationChanged::class => [
            LogEquipmentLocationChanged::class,
            WebSocketNotificationListener::class,
        ],

        
        KnowledgeBaseArticleCreated::class => [
            LogKnowledgeBaseArticleCreated::class,
            WebSocketNotificationListener::class,
        ],

        KnowledgeBaseArticleUpdated::class => [
            LogKnowledgeBaseArticleUpdated::class,
            WebSocketNotificationListener::class,
        ],

        
        SystemNotificationCreated::class => [
            LogSystemNotificationCreated::class,
            WebSocketNotificationListener::class,
        ],
    ];

    
     * Register any events for your application.

    public function boot(): void
    {
        
    }

    
     * Determine if events and listeners should be automatically discovered.

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
