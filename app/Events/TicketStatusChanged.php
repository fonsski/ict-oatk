<?php

namespace App\Events;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Ticket $ticket;
    public string $oldStatus;
    public string $newStatus;
    public ?User $user;

    
     * Create a new event instance.

    public function __construct(Ticket $ticket, string $oldStatus, string $newStatus, ?User $user = null)
    {
        $this->ticket = $ticket;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->user = $user;
    }
}
