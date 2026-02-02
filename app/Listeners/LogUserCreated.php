<?php

namespace App\Listeners;

use App\Events\UserCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LogUserCreated implements ShouldQueue
{
    use InteractsWithQueue;

    
     * Create the event listener.

    public function __construct()
    {
        
    }

    
     * Handle the event.

    public function handle(UserCreated $event): void
    {
        $user = $event->user;
        $createdBy = $event->createdBy;

        Log::info('User created', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_phone' => $user->phone,
            'user_role_id' => $user->role_id,
            'user_role_name' => $user->role?->name,
            'user_is_active' => $user->is_active,
            'created_by_user_id' => $createdBy?->id,
            'created_by_user_name' => $createdBy?->name,
            'created_at' => $user->created_at,
        ]);
    }
}
