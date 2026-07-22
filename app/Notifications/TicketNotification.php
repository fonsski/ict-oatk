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
            // Колонка type в таблице notifications хранит класс уведомления,
            // поэтому смысловой тип (new_ticket, ticket_assigned, ...)
            // кладём в полезную нагрузку — иначе он теряется.
            'type' => $this->notificationData['type'] ?? 'general',
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
            'type' => $this->notificationData['type'] ?? 'general',
            'title' => $this->notificationData['title'] ?? 'Уведомление',
            'message' => $this->notificationData['message'] ?? '',
            'icon' => $this->notificationData['icon'] ?? 'info',
            'color' => $this->notificationData['color'] ?? 'blue',
            'link' => $this->notificationData['link'] ?? null,
            'data' => $this->notificationData['data'] ?? [],
        ];
    }
}
