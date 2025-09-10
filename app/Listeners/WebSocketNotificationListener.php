<?php

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Events\TicketStatusChanged;
use App\Events\TicketAssigned;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class WebSocketNotificationListener implements ShouldQueue
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
    public function handle($event): void
    {
        try {
            $message = $this->prepareMessage($event);
            
            if ($message) {
                $this->sendToWebSocket($message);
            }
        } catch (\Exception $e) {
            Log::error('WebSocket notification error: ' . $e->getMessage());
        }
    }

    /**
     * Prepare message for WebSocket based on event type
     */
    private function prepareMessage($event): ?array
    {
        switch (get_class($event)) {
            case TicketCreated::class:
                return [
                    'type' => 'ticket_created',
                    'data' => [
                        'ticket_id' => $event->ticket->id,
                        'title' => $event->ticket->title,
                        'status' => $event->ticket->status,
                        'priority' => $event->ticket->priority,
                        'category' => $event->ticket->category,
                        'reporter_name' => $event->ticket->reporter_name,
                        'created_at' => $event->ticket->created_at->toISOString(),
                        'message' => "Создана новая заявка #{$event->ticket->id}: {$event->ticket->title}"
                    ],
                    'timestamp' => now()->toISOString()
                ];

            case TicketStatusChanged::class:
                return [
                    'type' => 'ticket_status_changed',
                    'data' => [
                        'ticket_id' => $event->ticket->id,
                        'title' => $event->ticket->title,
                        'old_status' => $event->oldStatus,
                        'new_status' => $event->newStatus,
                        'changed_by' => $event->user ? $event->user->name : 'Система',
                        'changed_at' => now()->toISOString(),
                        'message' => "Статус заявки #{$event->ticket->id} изменен с '{$event->oldStatus}' на '{$event->newStatus}'"
                    ],
                    'timestamp' => now()->toISOString()
                ];

            case TicketAssigned::class:
                return [
                    'type' => 'ticket_assigned',
                    'data' => [
                        'ticket_id' => $event->ticket->id,
                        'title' => $event->ticket->title,
                        'assigned_to' => $event->assignedUser->name,
                        'assigned_by' => $event->assignedBy ? $event->assignedBy->name : 'Система',
                        'assigned_at' => now()->toISOString(),
                        'message' => "Заявка #{$event->ticket->id} назначена на {$event->assignedUser->name}"
                    ],
                    'timestamp' => now()->toISOString()
                ];

            default:
                return null;
        }
    }

    /**
     * Send message to WebSocket server
     */
    private function sendToWebSocket(array $message): void
    {
        try {
            // Получаем хост из конфигурации или используем localhost
            $host = config('app.websocket_host', 'localhost');
            $port = config('app.websocket_port', 8080);
            
            // Отправляем HTTP запрос к WebSocket серверу для broadcast
            $response = Http::timeout(5)->post("http://{$host}:{$port}/broadcast", [
                'message' => $message
            ]);

            if (!$response->successful()) {
                Log::warning('WebSocket broadcast failed: ' . $response->body());
            } else {
                Log::info('WebSocket broadcast successful', ['type' => $message['type']]);
            }
        } catch (\Exception $e) {
            Log::error('WebSocket broadcast error: ' . $e->getMessage());
        }
    }
}
