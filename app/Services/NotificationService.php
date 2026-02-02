<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use App\Events\SystemNotificationCreated;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NotificationService
{
    protected $telegramBotController;
    protected $notificationLock = [];

    public function __construct()
    {
        
        
        $this->telegramBotController = null;
    }

    
     * Получить экземпляр TelegramBotController

    protected function getTelegramBotController()
    {
        if ($this->telegramBotController === null) {
            $this->telegramBotController = app(
                \App\Http\Controllers\TelegramBotController::class,
            );
        }
        return $this->telegramBotController;
    }

    
     * Отправить уведомление о снятии исполнителя с заявки

    public function notifyTicketUnassigned(Ticket $ticket, User $user)
    {
        try {
            
            $notification = [
                "title" => "Вы сняты с заявки",
                "message" => "Вы были сняты с заявки 
                "icon" => "info",
                "color" => "blue",
                "link" => route("tickets.show", $ticket),
            ];

            
            $user->notify(
                new \App\Notifications\TicketNotification($notification),
            );

            Log::info(
                "Отправлено уведомление о снятии с заявки 
            );
        } catch (\Exception $e) {
            Log::error(
                "Ошибка при отправке уведомления о снятии с заявки: " .
                    $e->getMessage(),
            );
        }
    }

    
     * Отправить уведомление в Telegram (только в обычном окружении)

    public function sendTelegramNotification($chatId, $message, $params = [])
    {
        
        
        if (env('LARAVEL_SAIL')) {
            Log::info("Telegram notification skipped in Docker environment", [
                'chat_id' => $chatId,
                'message_preview' => substr($message, 0, 100)
            ]);
            return;
        }

        try {
            $botman = app(\BotMan\BotMan\BotMan::class);
            $botman->say($message, $chatId, \BotMan\Drivers\Telegram\TelegramDriver::class, $params);
            
            Log::info("Telegram notification sent via NotificationService", [
                'chat_id' => $chatId,
                'message_preview' => substr($message, 0, 100)
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send Telegram notification via NotificationService: " . $e->getMessage());
        }
    }

    
     * Отправить уведомление о новой заявке

    function notifyNewTicket(Ticket $ticket)
    {
        
        if (
            \App\Models\SentTelegramNotification::wasNotificationSent(
                $ticket->id,
                "new_ticket",
            )
        ) {
            \Illuminate\Support\Facades\Log::info(
                "Уведомление для заявки 
            );
            return;
        }

        
        $lockKey = "notification_lock_ticket_{$ticket->id}";
        if (
            isset($this->notificationLock[$lockKey]) &&
            $this->notificationLock[$lockKey]
        ) {
            \Illuminate\Support\Facades\Log::info(
                "Уведомление для заявки 
            );
            return;
        }

        
        $this->notificationLock[$lockKey] = true;

        try {
            
            
            if (!env('LARAVEL_SAIL')) {
                
                $this->getTelegramBotController()->sendNewTicketNotification(
                    $ticket,
                );
            }

            
            \App\Models\SentTelegramNotification::registerSentNotification(
                $ticket->id,
                "new_ticket",
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error(
                "Ошибка отправки уведомления в Telegram: " . $e->getMessage(),
            );
        } finally {
            
            $this->notificationLock[$lockKey] = false;
        }

        try {
            
            $recipients = User::whereHas("role", function ($q) {
                $q->whereIn("slug", ["admin", "master", "technician"]);
            })
                ->where("is_active", true)
                ->get();

            
            $priorities = [
                "low" => "Низкий",
                "medium" => "Средний",
                "high" => "Высокий",
                "urgent" => "Срочный",
            ];

            $priorityLabel =
                $priorities[$ticket->priority] ??
                Str::ucfirst($ticket->priority);
            $priorityColors = [
                "low" => "green",
                "medium" => "yellow",
                "high" => "orange",
                "urgent" => "red",
            ];
            $priorityColor = $priorityColors[$ticket->priority] ?? "blue";

            
            $categories = [
                "hardware" => "Оборудование",
                "software" => "Программное обеспечение",
                "network" => "Сеть и интернет",
                "account" => "Учетная запись",
                "other" => "Другое",
            ];
            $categoryLabel =
                $categories[$ticket->category] ??
                Str::ucfirst($ticket->category);

            foreach ($recipients as $recipient) {
                
                $title = "Новая заявка";
                $message = "Создана новая заявка: {$ticket->title}";

                
                if (
                    in_array($recipient->role->slug, ["technician", "master"])
                ) {
                    $message = "Создана новая заявка ({$categoryLabel}, приоритет: {$priorityLabel}): {$ticket->title}";
                }

                $this->createNotification([
                    "user_id" => $recipient->id,
                    "type" => "new_ticket",
                    "title" => $title,
                    "message" => $message,
                    "data" => [
                        "ticket_id" => $ticket->id,
                        "ticket_title" => $ticket->title,
                        "ticket_priority" => $ticket->priority,
                        "ticket_priority_label" => $priorityLabel,
                        "ticket_priority_color" => $priorityColor,
                        "ticket_category" => $ticket->category,
                        "ticket_category_label" => $categoryLabel,
                        "reporter_name" => $ticket->reporter_name,
                        "recipient_role" => $recipient->role->slug,
                    ],
                    "url" => route("tickets.show", $ticket),
                ]);
            }

            Log::info(
                "Sent notifications for new ticket 
                    $recipients->count() .
                    " recipients",
            );
        } catch (\Exception $e) {
            Log::error(
                "Failed to send notifications for ticket 
                    $e->getMessage(),
            );
        }
    }

    
     * Отправить уведомление об изменении статуса заявки

    public function notifyTicketStatusChanged(
        Ticket $ticket,
        $oldStatus,
        $newStatus,
    ) {
        try {
            $recipients = collect();

            
            if ($ticket->user) {
                $recipients->push($ticket->user);
            }

            
            
            if (!$ticket->user && $ticket->reporter_email) {
                Log::info(
                    "Would send email notification to {$ticket->reporter_email} for ticket 
                );
                
            }

            
            if (
                $ticket->assignedTo &&
                $ticket->assignedTo->id !== optional($ticket->user)->id
            ) {
                $recipients->push($ticket->assignedTo);
            }

            
            $adminUsers = User::whereHas("role", function ($q) {
                $q->whereIn("slug", ["admin", "master"]);
            })
                ->where("is_active", true)
                ->get();

            $recipients = $recipients->merge($adminUsers)->unique("id");

            $statusLabels = [
                "open" => "Открыта",
                "in_progress" => "В работе",
                "resolved" => "Решена",
                "closed" => "Закрыта",
            ];

            $statusColors = [
                "open" => "blue",
                "in_progress" => "yellow",
                "resolved" => "green",
                "closed" => "gray",
            ];

            $statusLabel = $statusLabels[$newStatus] ?? $newStatus;

            
            foreach ($recipients as $recipient) {
                $isAuthor =
                    $ticket->user && $recipient->id === $ticket->user->id;
                $isAssignee =
                    $ticket->assignedTo &&
                    $recipient->id === $ticket->assignedTo->id;

                $title = "Изменение статуса заявки";
                $message = "Статус заявки \"{$ticket->title}\" изменен на: {$statusLabel}";

                
                if ($isAuthor && $newStatus === "in_progress") {
                    $message = "Ваша заявка \"{$ticket->title}\" взята в работу";
                    if ($ticket->assignedTo) {
                        $message .= " специалистом {$ticket->assignedTo->name}";
                    }
                } elseif ($isAuthor && $newStatus === "resolved") {
                    $message = "Ваша заявка \"{$ticket->title}\" решена";
                } elseif ($isAuthor && $newStatus === "closed") {
                    $message = "Ваша заявка \"{$ticket->title}\" закрыта";
                } elseif ($isAssignee && $newStatus === "in_progress") {
                    $message = "Вы взяли в работу заявку \"{$ticket->title}\"";
                }

                $this->createNotification([
                    "user_id" => $recipient->id,
                    "type" => "ticket_status_changed",
                    "title" => $title,
                    "message" => $message,
                    "data" => [
                        "ticket_id" => $ticket->id,
                        "ticket_title" => $ticket->title,
                        "old_status" => $oldStatus,
                        "new_status" => $newStatus,
                        "status_color" => $statusColors[$newStatus] ?? "blue",
                        "is_author" => $isAuthor,
                        "is_assignee" => $isAssignee,
                    ],
                    "url" => route("tickets.show", $ticket),
                ]);
            }

            Log::info(
                "Sent status change notifications for ticket 
                    $recipients->count() .
                    " recipients",
            );
        } catch (\Exception $e) {
            Log::error(
                "Failed to send status change notifications for ticket 
                    $e->getMessage(),
            );
        }
    }

    
     * Отправить уведомление о назначении заявки

    public function notifyTicketAssigned(Ticket $ticket, User $assignedUser)
    {
        try {
            $this->createNotification([
                "user_id" => $assignedUser->id,
                "type" => "ticket_assigned",
                "title" => "Назначена новая заявка",
                "message" => "Вам назначена заявка: {$ticket->title}",
                "data" => [
                    "ticket_id" => $ticket->id,
                    "ticket_title" => $ticket->title,
                    "ticket_priority" => $ticket->priority,
                    "reporter_name" => $ticket->reporter_name,
                ],
                "url" => route("tickets.show", $ticket),
            ]);

            Log::info(
                "Sent assignment notification for ticket 
            );
        } catch (\Exception $e) {
            Log::error(
                "Failed to send assignment notification for ticket 
                    $e->getMessage(),
            );
        }
    }

    
     * Получить уведомления для пользователя

    public function getUserNotifications(
        User $user,
        $limit = 10,
        $unreadOnly = false,
    ) {
        $query = $user->notifications()->latest();

        if ($unreadOnly) {
            $query->whereNull('read_at');
        }

        return $query->limit($limit)->get();
    }

    
     * Отметить уведомление как прочитанное

    public function markAsRead(User $user, $notificationId)
    {
        $notification = $user->notifications()->find($notificationId);
        
        if ($notification && !$notification->read_at) {
            $notification->markAsRead();
        }
    }

    
     * Отметить все уведомления как прочитанные

    public function markAllAsRead(User $user)
    {
        $user->unreadNotifications()->update(['read_at' => now()]);
    }

    
     * Получить количество непрочитанных уведомлений

    public function getUnreadCount(User $user)
    {
        return $user->unreadNotifications()->count();
    }

    
     * Создать уведомление

    public function createNotification(array $data)
    {
        $user = User::find($data['user_id']);
        
        if (!$user) {
            Log::error("User not found for notification", ['user_id' => $data['user_id']]);
            return;
        }

        $notificationData = [
            'type' => $data['type'] ?? 'general',
            'title' => $data['title'] ?? 'Уведомление',
            'message' => $data['message'] ?? '',
            'icon' => $data['icon'] ?? 'info',
            'color' => $data['color'] ?? 'blue',
            'link' => $data['url'] ?? null,
            'data' => $data['data'] ?? [],
        ];

        $user->notify(new \App\Notifications\TicketNotification($notificationData));

        
        $createdBy = \Illuminate\Support\Facades\Auth::check() ? \Illuminate\Support\Facades\Auth::user() : $user;
        event(new SystemNotificationCreated($user, $notificationData, $createdBy));
    }

    
     * Очистить старые уведомления

    public function cleanupOldNotifications($daysOld = 30)
    {
        $cutoffDate = now()->subDays($daysOld);
        
        $deletedCount = \App\Models\Notification::where('created_at', '<', $cutoffDate)->delete();
        
        Log::info("Cleaned up {$deletedCount} notifications older than {$daysOld} days");
        
        return $deletedCount;
    }

    
     * Получить статистику уведомлений

    public function getNotificationStats(User $user)
    {
        $notifications = $user->notifications();
        $recentDate = now()->subDays(7);

        return [
            "total" => $notifications->count(),
            "unread" => $user->unreadNotifications()->count(),
            "by_type" => $notifications->get()->groupBy("type")->map->count(),
            "recent" => $notifications->where("created_at", ">=", $recentDate)->count(),
        ];
    }
}
