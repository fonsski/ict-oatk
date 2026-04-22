<?php

namespace App\Listeners;

use App\Events\UserStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LogUserStatusChanged implements ShouldQueue
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
    public function handle(UserStatusChanged $event): void
    {
        $user = $event->user;
        $changedBy = $event->changedBy;

        Log::info('User status changed', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'old_status' => $event->oldStatus ? 'active' : 'inactive',
            'new_status' => $event->newStatus ? 'active' : 'inactive',
            'changed_by_user_id' => $changedBy?->id,
            'changed_by_user_name' => $changedBy?->name,
            'changed_at' => now(),
        ]);
    }
}
