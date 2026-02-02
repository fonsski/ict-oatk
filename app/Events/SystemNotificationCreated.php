<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SystemNotificationCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public array $notificationData;
    public ?User $createdBy;

    
     * Create a new event instance.

    public function __construct(User $user, array $notificationData, ?User $createdBy = null)
    {
        $this->user = $user;
        $this->notificationData = $notificationData;
        $this->createdBy = $createdBy;
    }
}
