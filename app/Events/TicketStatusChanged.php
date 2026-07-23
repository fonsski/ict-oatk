<?php

namespace App\Events;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Ticket $ticket;
    public string $oldStatus;
    public string $newStatus;
    public ?User $user;

    /**
     * Create a new event instance.
     */
    public function __construct(Ticket $ticket, string $oldStatus, string $newStatus, ?User $user = null)
    {
        $this->ticket = $ticket;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->user = $user;
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('staff');
    }

    public function broadcastAs(): string
    {
        return 'ticket.status';
    }

    public function broadcastWith(): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'message' => "Статус заявки #{$this->ticket->id}: {$this->newStatus}",
        ];
    }
}
