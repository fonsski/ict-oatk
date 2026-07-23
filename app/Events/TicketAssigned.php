<?php

namespace App\Events;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketAssigned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Ticket $ticket;
    public User $assignedUser;
    public ?User $assignedBy;

    /**
     * Create a new event instance.
     */
    public function __construct(Ticket $ticket, User $assignedUser, ?User $assignedBy = null)
    {
        $this->ticket = $ticket;
        $this->assignedUser = $assignedUser;
        $this->assignedBy = $assignedBy;
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('staff');
    }

    public function broadcastAs(): string
    {
        return 'ticket.assigned';
    }

    public function broadcastWith(): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'assigned_to' => $this->assignedUser->name,
            'message' => "Заявка #{$this->ticket->id} назначена на {$this->assignedUser->name}",
        ];
    }
}
