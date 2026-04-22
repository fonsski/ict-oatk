<?php

namespace App\Listeners;

use App\Events\TicketCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LogTicketCreated implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(TicketCreated $event): void
    {
        $ticket = $event->ticket;
        $user = $event->user;

        Log::info('Ticket created', [
            'ticket_id' => $ticket->id,
            'ticket_title' => $ticket->title,
            'ticket_category' => $ticket->category,
            'ticket_priority' => $ticket->priority,
            'ticket_status' => $ticket->status,
            'reporter_name' => $ticket->reporter_name,
            'reporter_phone' => $ticket->reporter_phone,
            'location_id' => $ticket->location_id,
            'room_id' => $ticket->room_id,
            'equipment_id' => $ticket->equipment_id,
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'created_at' => $ticket->created_at,
        ]);
    }
}
