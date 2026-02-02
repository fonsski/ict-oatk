<?php

namespace App\Events;

use App\Models\Equipment;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EquipmentLocationChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Equipment $equipment;
    public ?int $oldRoomId;
    public ?int $newRoomId;
    public ?User $user;

    
     * Create a new event instance.

    public function __construct(Equipment $equipment, ?int $oldRoomId, ?int $newRoomId, ?User $user = null)
    {
        $this->equipment = $equipment;
        $this->oldRoomId = $oldRoomId;
        $this->newRoomId = $newRoomId;
        $this->user = $user;
    }
}
