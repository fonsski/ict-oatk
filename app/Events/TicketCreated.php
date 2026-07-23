<?php

namespace App\Events;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketCreated implements ShouldBroadcast
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

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('staff');
    }

    public function broadcastAs(): string
    {
        return 'ticket.created';
    }

    /**
     * Лёгкий сигнал «заявка создана» — клиент по нему перезапрашивает доску.
     */
    public function broadcastWith(): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'title' => $this->ticket->title,
            'message' => "Новая заявка #{$this->ticket->id}: {$this->ticket->title}",
        ];
    }
}
