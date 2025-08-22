<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NotificationService
{
    /**
     * Отправить уведомление о новой заявке
     */
    public function notifyNewTicket(Ticket $ticket)
    {
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
        $cacheKey =
            "notifications_user_{$user->id}_limit_{$limit}_unread_" .
            ($unreadOnly ? "1" : "0");

        \Log::debug("Getting notifications for user", [
            "user_id" => $user->id,
            "limit" => $limit,
            "unread_only" => $unreadOnly,
            "cache_key" => $cacheKey,
        ]);

        // Временно отключаем кеширование для отладки
        $notifications = collect(session("notifications.{$user->id}", []));

        \Log::debug("Raw notifications from session", [
            "count" => $notifications->count(),
            "notifications" => $notifications->toArray(),
        ]);

        if ($unreadOnly) {
            $notifications = $notifications->where("read_at", null);
            \Log::debug("After unread filter", [
                "count" => $notifications->count(),
            ]);
        }

        $result = $notifications
            ->sortByDesc("created_at")
            ->take($limit)
            ->values();

        \Log::debug("Final notifications result", [
            "count" => $result->count(),
            "notifications" => $result->toArray(),
        ]);

        return $result;
    }

    /**
     * Отметить уведомление как прочитанное
     */
    public function markAsRead(User $user, $notificationId)
    {
        \Log::debug("Marking notification as read", [
            "user_id" => $user->id,
            "notification_id" => $notificationId,
        ]);

        $notifications = collect(session("notifications.{$user->id}", []));
        \Log::debug("Current notifications", [
            "count" => $notifications->count(),
        ]);

        $notifications = $notifications->map(function ($notification) use (
            $notificationId,
        ) {
            if ($notification["id"] === $notificationId) {
                \Log::debug("Found notification to mark as read", [
                    "notification" => $notification,
                ]);
                $notification["read_at"] = now()->toDateTimeString();
            }
            return $notification;
        });

        session(["notifications.{$user->id}" => $notifications->toArray()]);
        Cache::forget("notifications_user_{$user->id}_limit_10_unread_1");
        Cache::forget("notifications_user_{$user->id}_limit_10_unread_0");

        \Log::debug("Notification marked as read and cache cleared");
    }

    /**
     * Отметить все уведомления как прочитанные
     */
    public function markAllAsRead(User $user)
    {
        $notifications = collect(session("notifications.{$user->id}", []));

        $notifications = $notifications->map(function ($notification) {
            $notification["read_at"] = now()->toDateTimeString();
            return $notification;
        });

        session(["notifications.{$user->id}" => $notifications->toArray()]);
        Cache::forget("notifications_user_{$user->id}_limit_10_unread_1");
        Cache::forget("notifications_user_{$user->id}_limit_10_unread_0");
    }

    /**
     * Получить количество непрочитанных уведомлений
     */
    public function getUnreadCount(User $user)
    {
        $count = $this->getUserNotifications($user, 100, true)->count();
        \Log::debug("Unread notifications count", [
            "user_id" => $user->id,
            "count" => $count,
        ]);
        return $count;
    }

    /**
     * Создать уведомление (сохраняем в сессии для простоты)
     */
    public function createNotification(array $data)
    {
        \Log::debug("Creating notification", [
            "data" => $data,
        ]);

        $notification = array_merge($data, [
            "id" => uniqid(),
            "created_at" => now()->toDateTimeString(),
            "read_at" => null,
        ]);

        \Log::debug("Prepared notification", [
            "notification" => $notification,
        ]);

        $userId = $data["user_id"];
        $existingNotifications = collect(
            session("notifications.{$userId}", []),
        );

        \Log::debug("Existing notifications", [
            "user_id" => $userId,
            "count" => $existingNotifications->count(),
        ]);

        $existingNotifications->push($notification);

        // Ограничиваем количество уведомлений для каждого пользователя
        if ($existingNotifications->count() > 50) {
            $existingNotifications = $existingNotifications
                ->sortByDesc("created_at")
                ->take(50);
            \Log::debug("Limited notifications to 50");
        }

        $notificationsToStore = $existingNotifications->values()->toArray();
        session([
            "notifications.{$userId}" => $notificationsToStore,
        ]);

        \Log::debug("Notification stored in session", [
            "user_id" => $userId,
            "notification_id" => $notification["id"],
            "total_count" => count($notificationsToStore),
        ]);
    }

    /**
     * Очистить старые уведомления
     */
    public function cleanupOldNotifications($daysOld = 30)
    {
        // В реальном приложении здесь была бы очистка из базы данных
        // Для сессий это не так критично, так как они автоматически очищаются
        Log::info("Cleanup notifications older than {$daysOld} days");
    }

    /**
     * Получить статистику уведомлений
     */
    public function getNotificationStats(User $user)
    {
        $notifications = collect(session("notifications.{$user->id}", []));

        return [
            "total" => $notifications->count(),
            "unread" => $notifications->where("read_at", null)->count(),
            "by_type" => $notifications->groupBy("type")->map->count(),
            "recent" => $notifications
                ->where(
                    "created_at",
                    ">=",
                    now()->subDays(7)->toDateTimeString(),
                )
                ->count(),
        ];
    }
}
