<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use App\Models\SentTelegramNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TelegramNotificationService
{
    protected TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Отправляет уведомления о новых заявках
     */
    public function notifyNewTickets(): void
    {
        try {
            // Получаем новые заявки за последние 15 минут
            $fifteenMinutesAgo = now()->subMinutes(15);

            $newTickets = Ticket::where('created_at', '>=', $fifteenMinutesAgo)
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('sent_telegram_notifications')
                        ->whereRaw('sent_telegram_notifications.ticket_id = tickets.id')
                        ->where('notification_type', 'new_ticket');
                })
                ->get();

            if ($newTickets->isEmpty()) {
                return;
            }

            Log::info('Found new tickets to notify', [
                'count' => $newTickets->count(),
                'ticket_ids' => $newTickets->pluck('id')->toArray()
            ]);

            // Получаем пользователей с правами на уведомления
            $users = User::whereNotNull('telegram_id')
                ->whereHas('role', function ($query) {
                    $query->whereIn('slug', ['admin', 'master', 'technician']);
                })
                ->get();

            if ($users->isEmpty()) {
                Log::info('No users with Telegram ID to notify');
                return;
            }

            foreach ($newTickets as $ticket) {
                $this->notifyNewTicket($ticket, $users);
            }
        } catch (\Exception $e) {
            Log::error('Error in notifyNewTickets', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Отправляет уведомление о новой заявке
     */
    protected function notifyNewTicket(Ticket $ticket, $users): void
    {
        // Проверяем еще раз, чтобы избежать гонки условий
        if (SentTelegramNotification::wasNotificationSent($ticket->id, 'new_ticket')) {
            Log::info("Skipping already notified ticket #{$ticket->id}");
            return;
        }

        $ticketData = [
            'id' => $ticket->id,
            'title' => $ticket->title,
            'category' => $ticket->category,
            'priority' => $ticket->priority,
            'reporter_name' => $ticket->reporter_name
        ];

        $notifiedUserIds = [];

        foreach ($users as $user) {
            try {
                if ($this->telegramService->sendNewTicketNotification($user->telegram_id, $ticketData)) {
                    $notifiedUserIds[] = $user->id;
                    Log::info('Successfully sent new ticket notification', [
                        'ticket_id' => $ticket->id,
                        'user_id' => $user->id,
                        'telegram_id' => $user->telegram_id
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to send new ticket notification', [
                    'ticket_id' => $ticket->id,
                    'user_id' => $user->id,
                    'telegram_id' => $user->telegram_id,
                    'error' => $e->getMessage()
                ]);

                // Если ошибка связана с неверным telegram_id, очищаем его
                if (strpos($e->getMessage(), 'chat not found') !== false || 
                    strpos($e->getMessage(), 'user not found') !== false) {
                    $user->update(['telegram_id' => null]);
                    Log::info('Cleared invalid telegram_id for user', [
                        'user_id' => $user->id
                    ]);
                }
            }
        }

        // Регистрируем отправку уведомления
        if (!empty($notifiedUserIds)) {
            SentTelegramNotification::registerSentNotification(
                $ticket->id,
                'new_ticket',
                $notifiedUserIds
            );
        }
    }

    /**
     * Отправляет уведомление об изменении статуса заявки
     */
    public function notifyTicketStatusChange(Ticket $ticket, string $oldStatus, string $newStatus): void
    {
        try {
            // Получаем пользователей, которые должны получить уведомление
            $users = collect();

            // Добавляем исполнителя заявки
            if ($ticket->assigned_to_id) {
                $assignedUser = User::where('id', $ticket->assigned_to_id)
                    ->whereNotNull('telegram_id')
                    ->first();
                if ($assignedUser) {
                    $users->push($assignedUser);
                }
            }

            // Добавляем администраторов и мастеров
            $adminUsers = User::whereNotNull('telegram_id')
                ->whereHas('role', function ($query) {
                    $query->whereIn('slug', ['admin', 'master']);
                })
                ->get();
            $users = $users->merge($adminUsers);

            // Убираем дубликаты
            $users = $users->unique('id');

            if ($users->isEmpty()) {
                Log::info('No users to notify about ticket status change', [
                    'ticket_id' => $ticket->id
                ]);
                return;
            }

            $ticketData = [
                'id' => $ticket->id,
                'title' => $ticket->title,
                'assigned_to_name' => $ticket->assignedTo->name ?? 'Не назначен'
            ];

            foreach ($users as $user) {
                try {
                    $this->telegramService->sendTicketStatusNotification(
                        $user->telegram_id,
                        $ticketData,
                        $oldStatus,
                        $newStatus
                    );

                    Log::info('Successfully sent ticket status notification', [
                        'ticket_id' => $ticket->id,
                        'user_id' => $user->id,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send ticket status notification', [
                        'ticket_id' => $ticket->id,
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in notifyTicketStatusChange', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Отправляет уведомление о назначении заявки
     */
    public function notifyTicketAssignment(Ticket $ticket, ?User $oldAssignee, User $newAssignee): void
    {
        try {
            $users = collect();

            // Уведомляем нового исполнителя
            if ($newAssignee && $newAssignee->telegram_id) {
                $users->push($newAssignee);
            }

            // Уведомляем старого исполнителя (если он есть и отличается от нового)
            if ($oldAssignee && $oldAssignee->id !== $newAssignee->id && $oldAssignee->telegram_id) {
                $users->push($oldAssignee);
            }

            // Уведомляем администраторов
            $adminUsers = User::whereNotNull('telegram_id')
                ->whereHas('role', function ($query) {
                    $query->whereIn('slug', ['admin', 'master']);
                })
                ->get();
            $users = $users->merge($adminUsers);

            // Убираем дубликаты
            $users = $users->unique('id');

            if ($users->isEmpty()) {
                return;
            }

            $message = "👤 <b>Заявка #{$ticket->id} назначена</b>\n\n";
            $message .= "📋 <b>Название:</b> {$ticket->title}\n";
            $message .= "👤 <b>Назначена на:</b> {$newAssignee->name}\n";
            
            if ($oldAssignee && $oldAssignee->id !== $newAssignee->id) {
                $message .= "👤 <b>Была назначена на:</b> {$oldAssignee->name}\n";
            }
            
            $message .= "\n🔍 <code>/ticket_{$ticket->id}</code> - Подробнее";

            foreach ($users as $user) {
                try {
                    $this->telegramService->sendMessage($user->telegram_id, $message);
                    
                    Log::info('Successfully sent ticket assignment notification', [
                        'ticket_id' => $ticket->id,
                        'user_id' => $user->id,
                        'new_assignee_id' => $newAssignee->id
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send ticket assignment notification', [
                        'ticket_id' => $ticket->id,
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in notifyTicketAssignment', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Отправляет уведомление о комментарии к заявке
     */
    public function notifyTicketComment(Ticket $ticket, string $commentContent, User $commentAuthor): void
    {
        try {
            $users = collect();

            // Уведомляем исполнителя заявки (если это не автор комментария)
            if ($ticket->assigned_to_id && $ticket->assigned_to_id !== $commentAuthor->id) {
                $assignedUser = User::find($ticket->assigned_to_id);
                if ($assignedUser && $assignedUser->telegram_id) {
                    $users->push($assignedUser);
                }
            }

            // Уведомляем администраторов (если это не автор комментария)
            $adminUsers = User::whereNotNull('telegram_id')
                ->where('id', '!=', $commentAuthor->id)
                ->whereHas('role', function ($query) {
                    $query->whereIn('slug', ['admin', 'master']);
                })
                ->get();
            $users = $users->merge($adminUsers);

            // Убираем дубликаты
            $users = $users->unique('id');

            if ($users->isEmpty()) {
                return;
            }

            $message = "💬 <b>Новый комментарий к заявке #{$ticket->id}</b>\n\n";
            $message .= "📋 <b>Заявка:</b> {$ticket->title}\n";
            $message .= "👤 <b>Автор:</b> {$commentAuthor->name}\n";
            $message .= "💬 <b>Комментарий:</b>\n";
            $message .= substr($commentContent, 0, 200) . (strlen($commentContent) > 200 ? '...' : '');
            $message .= "\n\n🔍 <code>/ticket_{$ticket->id}</code> - Подробнее";

            foreach ($users as $user) {
                try {
                    $this->telegramService->sendMessage($user->telegram_id, $message);
                    
                    Log::info('Successfully sent ticket comment notification', [
                        'ticket_id' => $ticket->id,
                        'user_id' => $user->id,
                        'comment_author_id' => $commentAuthor->id
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send ticket comment notification', [
                        'ticket_id' => $ticket->id,
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in notifyTicketComment', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
