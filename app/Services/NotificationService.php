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
        // Отложенная инициализация контроллера телеграм-бота,
        // чтобы избежать циклической зависимости
        $this->telegramBotController = null;
    }

    /**
     * Получить экземпляр TelegramBotController
     */
    protected function getTelegramBotController()
    {
        if ($this->telegramBotController === null) {
            $this->telegramBotController = app(
                \App\Http\Controllers\TelegramBotController::class,
            );
        }
        return $this->telegramBotController;
    }

    /**
     * Отправить уведомление о снятии исполнителя с заявки
     */
    public function notifyTicketUnassigned(Ticket $ticket, User $user)
    {
        try {
            // Создаем уведомление о снятии с заявки
            $notification = [
                "title" => "Вы сняты с заявки",
                "message" => "Вы были сняты с заявки #{$ticket->id}: \"{$ticket->title}\"",
                "icon" => "info",
                "color" => "blue",
                "link" => route("tickets.show", $ticket),
            ];

            // Отправляем уведомление в базу данных
            $user->notify(
                new \App\Notifications\TicketNotification($notification),
            );

            Log::info(
                "Отправлено уведомление о снятии с заявки #{$ticket->id} пользователю {$user->name}",
            );
        } catch (\Exception $e) {
            Log::error(
                "Ошибка при отправке уведомления о снятии с заявки: " .
                    $e->getMessage(),
            );
        }
    }

    /**
     * Отправить уведомление о новой заявке
     */
    function notifyNewTicket(Ticket $ticket)
    {
        // Проверяем, не отправляли ли мы уже уведомление для этой заявки
        if (
            \App\Models\SentTelegramNotification::wasNotificationSent(
                $ticket->id,
                "new_ticket",
            )
        ) {
            \Illuminate\Support\Facades\Log::info(
                "Уведомление для заявки #{$ticket->id} уже было отправлено ранее. Пропускаем.",
            );
            return;
        }

        // Используем мьютекс для предотвращения одновременного выполнения
        $lockKey = "notification_lock_ticket_{$ticket->id}";
        if (
            isset($this->notificationLock[$lockKey]) &&
            $this->notificationLock[$lockKey]
        ) {
            \Illuminate\Support\Facades\Log::info(
                "Уведомление для заявки #{$ticket->id} уже обрабатывается. Пропускаем.",
            );
            return;
        }

        // Устанавливаем блокировку
        $this->notificationLock[$lockKey] = true;

        try {
            // Отправка уведомления в Telegram
            $this->getTelegramBotController()->sendNewTicketNotification(
                $ticket,
            );

            // Регистрируем отправленное уведомление в базе данных
            \App\Models\SentTelegramNotification::registerSentNotification(
                $ticket->id,
                "new_ticket",
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error(
                "Ошибка отправки уведомления в Telegram: " . $e->getMessage(),
            );
        } finally {
            // Снимаем блокировку в любом случае
            $this->notificationLock[$lockKey] = false;
        }

        try {
            // Получаем всех пользователей, которые должны получить уведомление
            $recipients = User::whereHas("role", function ($q) {
                $q->whereIn("slug", ["admin", "master", "technician"]);
            })
                ->where("is_active", true)
                ->get();

            // Определяем приоритет для отображения
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

            // Категории заявок
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
                // Разные сообщения для разных ролей
                $title = "Новая заявка";
                $message = "Создана новая заявка: {$ticket->title}";

                // Более информативное сообщение для технических специалистов
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
                "Sent notifications for new ticket #{$ticket->id} to " .
                    $recipients->count() .
                    " recipients",
            );
        } catch (\Exception $e) {
            Log::error(
                "Failed to send notifications for ticket #{$ticket->id}: " .
                    $e->getMessage(),
            );
        }
    }

    /**
     * Отправить уведомление об изменении статуса заявки
     */
    public function notifyTicketStatusChanged(
        Ticket $ticket,
        $oldStatus,
        $newStatus,
    ) {
        try {
            $recipients = collect();

            // Всегда уведомляем автора заявки, даже если он не авторизованный пользователь
            if ($ticket->user) {
                $recipients->push($ticket->user);
            }

            // Если автор заявки не авторизованный пользователь, но указана его почта,
            // все равно отправляем уведомление по электронной почте (в реальной системе)
            if (!$ticket->user && $ticket->reporter_email) {
                Log::info(
                    "Would send email notification to {$ticket->reporter_email} for ticket #{$ticket->id} status change",
                );
                // В реальной системе здесь был бы код для отправки email
            }

            // Уведомляем назначенного исполнителя
            if (
                $ticket->assignedTo &&
                $ticket->assignedTo->id !== optional($ticket->user)->id
            ) {
                $recipients->push($ticket->assignedTo);
            }

            // Уведомляем администраторов и мастеров
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

            // Разные сообщения для разных типов получателей
            foreach ($recipients as $recipient) {
                $isAuthor =
                    $ticket->user && $recipient->id === $ticket->user->id;
                $isAssignee =
                    $ticket->assignedTo &&
                    $recipient->id === $ticket->assignedTo->id;

                $title = "Изменение статуса заявки";
                $message = "Статус заявки \"{$ticket->title}\" изменен на: {$statusLabel}";

                // Персонализированные сообщения в зависимости от роли получателя
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
                "Sent status change notifications for ticket #{$ticket->id} to " .
                    $recipients->count() .
                    " recipients",
            );
        } catch (\Exception $e) {
            Log::error(
                "Failed to send status change notifications for ticket #{$ticket->id}: " .
                    $e->getMessage(),
            );
        }
    }

    /**
     * Отправить уведомление о назначении заявки
     */
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
                "Sent assignment notification for ticket #{$ticket->id} to user #{$assignedUser->id}",
            );
        } catch (\Exception $e) {
            Log::error(
                "Failed to send assignment notification for ticket #{$ticket->id}: " .
                    $e->getMessage(),
            );
        }
    }

    /**
     * Получить уведомления для пользователя
     */
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

    /**
     * Отметить уведомление как прочитанное
     */
    public function markAsRead(User $user, $notificationId)
    {
        $notification = $user->notifications()->find($notificationId);
        
        if ($notification && !$notification->read_at) {
            $notification->markAsRead();
        }
    }

    /**
     * Отметить все уведомления как прочитанные
     */
    public function markAllAsRead(User $user)
    {
        $user->unreadNotifications()->update(['read_at' => now()]);
    }

    /**
     * Получить количество непрочитанных уведомлений
     */
    public function getUnreadCount(User $user)
    {
        return $user->unreadNotifications()->count();
    }

    /**
     * Создать уведомление
     */
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

        // Отправляем событие для WebSocket уведомления
        event(new SystemNotificationCreated($user, $notificationData, auth()->user()));
    }

    /**
     * Очистить старые уведомления
     */
    public function cleanupOldNotifications($daysOld = 30)
    {
        $cutoffDate = now()->subDays($daysOld);
        
        $deletedCount = \App\Models\Notification::where('created_at', '<', $cutoffDate)->delete();
        
        Log::info("Cleaned up {$deletedCount} notifications older than {$daysOld} days");
        
        return $deletedCount;
    }

    /**
     * Получить статистику уведомлений
     */
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
