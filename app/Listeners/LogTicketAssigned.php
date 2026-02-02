<?php

namespace App\Listeners;

use App\Events\TicketAssigned;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LogTicketAssigned implements ShouldQueue
{
    use InteractsWithQueue;

    
     * Create the event listener.

    public function __construct()
    {
        
    }

    
     * Handle the event.

    public function handle(TicketAssigned $event): void
    {
        $ticket = $event->ticket;
        $assignedUser = $event->assignedUser;
        $assignedBy = $event->assignedBy;

        Log::info('Ticket assigned', [
            'ticket_id' => $ticket->id,
            'ticket_title' => $ticket->title,
            'assigned_to_user_id' => $assignedUser->id,
            'assigned_to_user_name' => $assignedUser->name,
            'assigned_by_user_id' => $assignedBy?->id,
            'assigned_by_user_name' => $assignedBy?->name,
            'assigned_at' => now(),
        ]);
    }
}
