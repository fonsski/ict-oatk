<?php

namespace App\Listeners;

use App\Events\SystemNotificationCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LogSystemNotificationCreated implements ShouldQueue
{
    use InteractsWithQueue;

    
     * Create the event listener.

    public function __construct()
    {
        
    }

    
     * Handle the event.

    public function handle(SystemNotificationCreated $event): void
    {
        $user = $event->user;
        $notificationData = $event->notificationData;
        $createdBy = $event->createdBy;
        
        Log::info('System notification created', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'notification_type' => $notificationData['type'] ?? 'unknown',
            'notification_title' => $notificationData['title'] ?? 'No title',
            'created_by' => $createdBy ? $createdBy->name : 'System',
            'created_at' => now()->toISOString(),
        ]);
    }
}
