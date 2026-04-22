<?php

namespace App\Listeners;

use App\Events\EquipmentStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LogEquipmentStatusChanged implements ShouldQueue
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
    public function handle(EquipmentStatusChanged $event): void
    {
        $equipment = $event->equipment;
        $user = $event->user;
        
        Log::info('Equipment status changed', [
            'equipment_id' => $equipment->id,
            'equipment_name' => $equipment->name,
            'inventory_number' => $equipment->inventory_number,
            'old_status' => $event->oldStatus,
            'new_status' => $event->newStatus,
            'user_id' => $user ? $user->id : null,
            'user_name' => $user ? $user->name : 'Unknown',
            'changed_at' => now()->toISOString(),
        ]);
    }
}
