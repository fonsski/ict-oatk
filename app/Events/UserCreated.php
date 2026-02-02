<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public ?User $createdBy;

    
     * Create a new event instance.

    public function __construct(User $user, ?User $createdBy = null)
    {
        $this->user = $user;
        $this->createdBy = $createdBy;
    }
}
