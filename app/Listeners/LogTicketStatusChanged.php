<?php

namespace App\Listeners;

use App\Events\TicketStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LogTicketStatusChanged implements ShouldQueue
{
    use InteractsWithQueue;

    
     * Create the event listener.

    public function __construct()
    {
        
    }

    
     * Handle the event.

    public function handle(TicketStatusChanged $event): void
    {
        $ticket = $event->ticket;
        $user = $event->user;

        Log::info('Ticket status changed', [
            'ticket_id' => $ticket->id,
            'ticket_title' => $ticket->title,
            'old_status' => $event->oldStatus,
            'new_status' => $event->newStatus,
            'changed_by_user_id' => $user?->id,
            'changed_by_user_name' => $user?->name,
            'assigned_to_id' => $ticket->assigned_to_id,
            'changed_at' => now(),
        ]);
    }
}
