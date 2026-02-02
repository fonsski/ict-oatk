<?php

namespace App\Events;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketAssigned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Ticket $ticket;
    public User $assignedUser;
    public ?User $assignedBy;

    
     * Create a new event instance.

    public function __construct(Ticket $ticket, User $assignedUser, ?User $assignedBy = null)
    {
        $this->ticket = $ticket;
        $this->assignedUser = $assignedUser;
        $this->assignedBy = $assignedBy;
    }
}
