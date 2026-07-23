<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SystemNotificationCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public array $notificationData;
    public ?User $createdBy;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, array $notificationData, ?User $createdBy = null)
    {
        $this->user = $user;
        $this->notificationData = $notificationData;
        $this->createdBy = $createdBy;
    }

    /**
     * Персональный канал получателя — уведомление видит только он.
     */
    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("user.{$this->user->id}");
    }

    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    public function broadcastWith(): array
    {
        return [
            'title' => $this->notificationData['title'] ?? 'Уведомление',
            'message' => $this->notificationData['message'] ?? '',
            'color' => $this->notificationData['color'] ?? 'blue',
            'type' => $this->notificationData['type'] ?? 'general',
        ];
    }
}
