<?php

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Events\TicketStatusChanged;
use App\Events\TicketAssigned;
use App\Events\TicketCommentCreated;
use App\Events\UserCreated;
use App\Events\UserStatusChanged;
use App\Events\EquipmentStatusChanged;
use App\Events\EquipmentLocationChanged;
use App\Events\KnowledgeBaseArticleCreated;
use App\Events\KnowledgeBaseArticleUpdated;
use App\Events\SystemNotificationCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class WebSocketNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    
     * Create the event listener.

    public function __construct()
    {
        
    }

    
     * Handle the event.

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

    
     * Prepare message for WebSocket based on event type

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
                        'message' => "Создана новая заявка 
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
                        'message' => "Статус заявки 
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
                        'message' => "Заявка 
                    ],
                    'timestamp' => now()->toISOString()
                ];

            case TicketCommentCreated::class:
                $commentType = $event->comment->is_system ? 'системный комментарий' : 'комментарий';
                return [
                    'type' => 'ticket_comment_created',
                    'data' => [
                        'comment_id' => $event->comment->id,
                        'ticket_id' => $event->comment->ticket_id,
                        'ticket_title' => $event->comment->ticket->title ?? 'Заявка 
                        'user_name' => $event->user ? $event->user->name : 'Система',
                        'content' => $event->comment->content,
                        'is_system' => $event->comment->is_system,
                        'created_at' => $event->comment->created_at->toISOString(),
                        'message' => "Добавлен {$commentType} к заявке 
                    ],
                    'timestamp' => now()->toISOString()
                ];

            case UserCreated::class:
                return [
                    'type' => 'user_created',
                    'data' => [
                        'user_id' => $event->user->id,
                        'name' => $event->user->name,
                        'phone' => $event->user->phone,
                        'role' => $event->user->role->name ?? 'Не указана',
                        'is_active' => $event->user->is_active,
                        'created_by' => $event->createdBy ? $event->createdBy->name : 'Система',
                        'created_at' => $event->user->created_at->toISOString(),
                        'message' => "Создан новый пользователь: {$event->user->name}"
                    ],
                    'timestamp' => now()->toISOString()
                ];

            case UserStatusChanged::class:
                $statusText = $event->newStatus ? 'активирован' : 'деактивирован';
                return [
                    'type' => 'user_status_changed',
                    'data' => [
                        'user_id' => $event->user->id,
                        'name' => $event->user->name,
                        'old_status' => $event->oldStatus,
                        'new_status' => $event->newStatus,
                        'changed_by' => $event->changedBy ? $event->changedBy->name : 'Система',
                        'changed_at' => now()->toISOString(),
                        'message' => "Пользователь {$event->user->name} {$statusText}"
                    ],
                    'timestamp' => now()->toISOString()
                ];

            case EquipmentStatusChanged::class:
                return [
                    'type' => 'equipment_status_changed',
                    'data' => [
                        'equipment_id' => $event->equipment->id,
                        'name' => $event->equipment->name,
                        'inventory_number' => $event->equipment->inventory_number,
                        'old_status' => $event->oldStatus,
                        'new_status' => $event->newStatus,
                        'changed_by' => $event->user ? $event->user->name : 'Система',
                        'changed_at' => now()->toISOString(),
                        'message' => "Статус оборудования '{$event->equipment->name}' изменен с '{$event->oldStatus}' на '{$event->newStatus}'"
                    ],
                    'timestamp' => now()->toISOString()
                ];

            case EquipmentLocationChanged::class:
                $oldRoom = $event->oldRoomId ? "кабинет 
                $newRoom = $event->newRoomId ? "кабинет 
                return [
                    'type' => 'equipment_location_changed',
                    'data' => [
                        'equipment_id' => $event->equipment->id,
                        'name' => $event->equipment->name,
                        'inventory_number' => $event->equipment->inventory_number,
                        'old_room_id' => $event->oldRoomId,
                        'new_room_id' => $event->newRoomId,
                        'changed_by' => $event->user ? $event->user->name : 'Система',
                        'changed_at' => now()->toISOString(),
                        'message' => "Оборудование '{$event->equipment->name}' перемещено из {$oldRoom} в {$newRoom}"
                    ],
                    'timestamp' => now()->toISOString()
                ];

            case KnowledgeBaseArticleCreated::class:
                return [
                    'type' => 'knowledge_article_created',
                    'data' => [
                        'article_id' => $event->article->id,
                        'title' => $event->article->title,
                        'slug' => $event->article->slug,
                        'category' => $event->article->category ? $event->article->category->name : 'Без категории',
                        'author' => $event->user ? $event->user->name : 'Неизвестно',
                        'created_at' => $event->article->created_at->toISOString(),
                        'message' => "Создана новая статья в базе знаний: '{$event->article->title}'"
                    ],
                    'timestamp' => now()->toISOString()
                ];

            case KnowledgeBaseArticleUpdated::class:
                return [
                    'type' => 'knowledge_article_updated',
                    'data' => [
                        'article_id' => $event->article->id,
                        'title' => $event->article->title,
                        'slug' => $event->article->slug,
                        'category' => $event->article->category ? $event->article->category->name : 'Без категории',
                        'updated_by' => $event->user ? $event->user->name : 'Неизвестно',
                        'updated_at' => $event->article->updated_at->toISOString(),
                        'message' => "Обновлена статья в базе знаний: '{$event->article->title}'"
                    ],
                    'timestamp' => now()->toISOString()
                ];

            case SystemNotificationCreated::class:
                return [
                    'type' => 'system_notification_created',
                    'data' => [
                        'user_id' => $event->user->id,
                        'user_name' => $event->user->name,
                        'notification_type' => $event->notificationData['type'] ?? 'unknown',
                        'title' => $event->notificationData['title'] ?? 'Уведомление',
                        'message' => $event->notificationData['message'] ?? '',
                        'icon' => $event->notificationData['icon'] ?? 'info',
                        'color' => $event->notificationData['color'] ?? 'blue',
                        'created_by' => $event->createdBy ? $event->createdBy->name : 'Система',
                        'created_at' => now()->toISOString(),
                        'message' => "Новое уведомление для {$event->user->name}: {$event->notificationData['title']}"
                    ],
                    'timestamp' => now()->toISOString()
                ];

            default:
                return null;
        }
    }

    
     * Send message to WebSocket server

    private function sendToWebSocket(array $message): void
    {
        try {
            
            if (app()->environment('local') && env('LARAVEL_SAIL')) {
                
                $host = config('app.websocket_docker_host', 'websocket-server');
            } else {
                
                $host = config('app.websocket_host', 'localhost');
            }
            
            $port = config('app.websocket_port', 8080);
            
            
            $response = Http::timeout(5)->post("http:
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
