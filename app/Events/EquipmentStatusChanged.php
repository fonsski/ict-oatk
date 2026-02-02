<?php

namespace App\Events;

use App\Models\Equipment;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EquipmentStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Equipment $equipment;
    public string $oldStatus;
    public string $newStatus;
    public ?User $user;

    
     * Create a new event instance.

    public function __construct(Equipment $equipment, string $oldStatus, string $newStatus, ?User $user = null)
    {
        $this->equipment = $equipment;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->user = $user;
    }
}
