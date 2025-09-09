<?php

namespace App\Events;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Ticket $ticket;
    public ?User $user;

    /**
     * Create a new event instance.
     */
    public function __construct(Ticket $ticket, ?User $user = null)
    {
        $this->ticket = $ticket;
        $this->user = $user;
    }
}
