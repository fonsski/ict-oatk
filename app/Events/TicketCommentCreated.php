<?php

namespace App\Events;

use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketCommentCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public TicketComment $comment;
    public ?User $user;

    
     * Create a new event instance.

    public function __construct(TicketComment $comment, ?User $user = null)
    {
        $this->comment = $comment;
        $this->user = $user;
    }
}
