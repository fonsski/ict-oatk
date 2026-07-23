<?php

namespace App\Events;

use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketCommentCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public TicketComment $comment;
    public ?User $user;

    /**
     * Create a new event instance.
     */
    public function __construct(TicketComment $comment, ?User $user = null)
    {
        $this->comment = $comment;
        $this->user = $user;
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('staff');
    }

    public function broadcastAs(): string
    {
        return 'ticket.comment';
    }

    public function broadcastWith(): array
    {
        return [
            'ticket_id' => $this->comment->ticket_id,
            'comment_id' => $this->comment->id,
            'message' => "Новый комментарий к заявке #{$this->comment->ticket_id}",
        ];
    }
}
