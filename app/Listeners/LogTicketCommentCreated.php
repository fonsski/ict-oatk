<?php

namespace App\Listeners;

use App\Events\TicketCommentCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LogTicketCommentCreated implements ShouldQueue
{
    use InteractsWithQueue;

    
     * Create the event listener.

    public function __construct()
    {
        
    }

    
     * Handle the event.

    public function handle(TicketCommentCreated $event): void
    {
        $comment = $event->comment;
        $user = $event->user;
        
        Log::info('Ticket comment created', [
            'comment_id' => $comment->id,
            'ticket_id' => $comment->ticket_id,
            'user_id' => $comment->user_id,
            'user_name' => $user ? $user->name : 'Unknown',
            'is_system' => $comment->is_system,
            'content_length' => strlen($comment->content),
            'created_at' => $comment->created_at->toISOString(),
        ]);
    }
}
