<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class TicketNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $notificationData;

    public function __construct(array $notificationData)
    {
        $this->notificationData = $notificationData;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return new DatabaseMessage([
            'title' => $this->notificationData['title'] ?? 'Уведомление',
            'message' => $this->notificationData['message'] ?? '',
            'icon' => $this->notificationData['icon'] ?? 'info',
            'color' => $this->notificationData['color'] ?? 'blue',
            'link' => $this->notificationData['link'] ?? null,
            'data' => $this->notificationData['data'] ?? [],
        ]);
    }

    public function toArray($notifiable)
    {
        return [
            'title' => $this->notificationData['title'] ?? 'Уведомление',
            'message' => $this->notificationData['message'] ?? '',
            'icon' => $this->notificationData['icon'] ?? 'info',
            'color' => $this->notificationData['color'] ?? 'blue',
            'link' => $this->notificationData['link'] ?? null,
            'data' => $this->notificationData['data'] ?? [],
        ];
    }
}
