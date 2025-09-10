<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class TelegramCommandService
{
    protected TelegramService $telegramService;
    protected TelegramAuthService $authService;

    public function __construct(TelegramService $telegramService, TelegramAuthService $authService)
    {
        $this->telegramService = $telegramService;
        $this->authService = $authService;
    }

    /**
     * Обрабатывает команду /start
     */
    public function handleStart(int $chatId): bool
    {
        $message = "👋 <b>Добро пожаловать в систему управления заявками!</b>\n\n";
        $message .= "Для начала работы необходимо авторизоваться.\n\n";
        $message .= "📋 <b>Доступные команды:</b>\n";
        $message .= "• <code>/login</code> - Авторизация\n";
        $message .= "• <code>/help</code> - Справка";

        return $this->telegramService->sendMessage($chatId, $message);
    }

    /**
     * Обрабатывает команду /help
     */
    public function handleHelp(int $chatId): bool
    {
        $message = "📋 <b>Справка по командам</b>\n\n";
        
        if ($this->authService->isUserAuthenticated($chatId)) {
            $message .= "🔐 <b>Авторизованные команды:</b>\n";
            $message .= "• <code>/tickets</code> - Список всех заявок\n";
            $message .= "• <code>/active</code> - Активные заявки в работе\n";
            $message .= "• <code>/ticket_123</code> - Подробности заявки #123\n";
            $message .= "• <code>/start_ticket_123</code> - Взять заявку #123 в работу\n";
            $message .= "• <code>/assign_123</code> - Назначить заявку #123 себе\n";
            $message .= "• <code>/resolve_123</code> - Отметить заявку #123 как решенную\n";
            $message .= "• <code>/logout</code> - Выйти из системы\n\n";
        } else {
            $message .= "🔓 <b>Команды без авторизации:</b>\n";
            $message .= "• <code>/login</code> - Авторизация в системе\n\n";
        }
        
        $message .= "📞 <b>Общие команды:</b>\n";
        $message .= "• <code>/start</code> - Начать работу с ботом\n";
        $message .= "• <code>/help</code> - Показать эту справку";

        return $this->telegramService->sendMessage($chatId, $message);
    }

    /**
     * Обрабатывает команду /tickets
     */
    public function handleTickets(int $chatId): bool
    {
        $user = $this->authService->getAuthenticatedUser($chatId);
        if (!$user) {
            return $this->sendAuthRequired($chatId);
        }

        if (!$user->canManageTickets()) {
            $message = "❌ У вас нет прав для просмотра заявок.";
            return $this->telegramService->sendMessage($chatId, $message);
        }

        // Получаем заявки в зависимости от роли пользователя
        if ($user->isAdmin() || $user->isMaster()) {
            $tickets = Ticket::where('status', '!=', 'closed')
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();
        } else {
            $tickets = Ticket::where('status', '!=', 'closed')
                ->where(function ($query) use ($user) {
                    $query->where('assigned_to_id', $user->id)
                          ->orWhereNull('assigned_to_id');
                })
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();
        }

        if ($tickets->isEmpty()) {
            $message = "📋 Активных заявок не найдено.";
            return $this->telegramService->sendMessage($chatId, $message);
        }

        $message = "📋 <b>Список активных заявок:</b>\n\n";

        foreach ($tickets as $ticket) {
            $status = $this->getStatusEmoji($ticket->status) . " " . $this->getHumanReadableStatus($ticket->status);
            $priority = $this->getPriorityEmoji($ticket->priority) . " " . ucfirst($ticket->priority);

            $message .= "🆔 <b>#{$ticket->id}</b>: {$ticket->title}\n";
            $message .= "📊 Статус: {$status}\n";
            $message .= "⚡ Приоритет: {$priority}\n";

            if ($ticket->assigned_to_id) {
                $assignedTo = $ticket->assignedTo->name ?? "Неизвестно";
                $message .= "👤 Исполнитель: {$assignedTo}\n";
            } else {
                $message .= "👤 Исполнитель: Не назначен\n";
            }

            $message .= "📅 Создано: " . $ticket->created_at->format("d.m.Y H:i") . "\n";
            $message .= "🔍 <code>/ticket_{$ticket->id}</code> - Подробнее\n\n";
        }

        return $this->telegramService->sendMessage($chatId, $message);
    }

    /**
     * Обрабатывает команду /active
     */
    public function handleActive(int $chatId): bool
    {
        $user = $this->authService->getAuthenticatedUser($chatId);
        if (!$user) {
            return $this->sendAuthRequired($chatId);
        }

        if (!$user->canManageTickets()) {
            $message = "❌ У вас нет прав для просмотра заявок.";
            return $this->telegramService->sendMessage($chatId, $message);
        }

        // Получаем только заявки в работе
        if ($user->isAdmin() || $user->isMaster()) {
            $tickets = Ticket::where('status', 'in_progress')
                ->orderBy('updated_at', 'desc')
                ->take(15)
                ->get();
        } else {
            $tickets = Ticket::where('status', 'in_progress')
                ->where('assigned_to_id', $user->id)
                ->orderBy('updated_at', 'desc')
                ->take(15)
                ->get();
        }

        if ($tickets->isEmpty()) {
            $message = "🔄 Активных заявок в работе не найдено.";
            return $this->telegramService->sendMessage($chatId, $message);
        }

        $message = "🔄 <b>Активные заявки в работе:</b>\n\n";

        foreach ($tickets as $ticket) {
            $priority = $this->getPriorityEmoji($ticket->priority) . " " . ucfirst($ticket->priority);
            
            $message .= "🆔 <b>#{$ticket->id}</b>: {$ticket->title}\n";
            $message .= "⚡ Приоритет: {$priority}\n";
            
            if ($ticket->assignedTo) {
                $message .= "👤 Исполнитель: {$ticket->assignedTo->name}\n";
            }
            
            $message .= "📅 Взята в работу: " . $ticket->updated_at->format("d.m.Y H:i") . "\n";
            $message .= "📝 Заявитель: {$ticket->reporter_name}\n";
            
            // Добавляем кнопки действий
            if ($ticket->assigned_to_id === $user->id) {
                $message .= "✅ <code>/resolve_{$ticket->id}</code> - Отметить решенной\n";
            }
            $message .= "🔍 <code>/ticket_{$ticket->id}</code> - Подробнее\n\n";
        }

        $message .= "💡 Используйте <code>/tickets</code> для просмотра всех заявок";

        return $this->telegramService->sendMessage($chatId, $message);
    }

    /**
     * Обрабатывает команду просмотра деталей заявки
     */
    public function handleTicketDetails(int $chatId, int $ticketId): bool
    {
        $user = $this->authService->getAuthenticatedUser($chatId);
        if (!$user) {
            return $this->sendAuthRequired($chatId);
        }

        if (!$user->canManageTickets()) {
            $message = "❌ У вас нет прав для просмотра заявок.";
            return $this->telegramService->sendMessage($chatId, $message);
        }

        $ticket = Ticket::find($ticketId);
        if (!$ticket) {
            $message = "❌ Заявка с ID {$ticketId} не найдена.";
            return $this->telegramService->sendMessage($chatId, $message);
        }

        $status = $this->getStatusEmoji($ticket->status) . " " . $this->getHumanReadableStatus($ticket->status);
        $priority = $this->getPriorityEmoji($ticket->priority) . " " . ucfirst($ticket->priority);

        $message = "🔍 <b>Детали заявки #{$ticket->id}</b>\n\n";
        $message .= "📋 <b>Название:</b> {$ticket->title}\n";
        $message .= "📂 <b>Категория:</b> {$ticket->category}\n";
        $message .= "📊 <b>Статус:</b> {$status}\n";
        $message .= "⚡ <b>Приоритет:</b> {$priority}\n\n";

        $message .= "📝 <b>Описание:</b>\n{$ticket->description}\n\n";

        $message .= "👤 <b>Заявитель:</b> {$ticket->reporter_name}\n";
        if ($ticket->reporter_email) {
            $message .= "📧 <b>Email:</b> {$ticket->reporter_email}\n";
        }
        if ($ticket->reporter_phone) {
            $message .= "📞 <b>Телефон:</b> {$ticket->reporter_phone}\n";
        }

        $message .= "\n📍 <b>Местоположение:</b> ";
        if ($ticket->location) {
            $message .= $ticket->location->name;
            if ($ticket->room) {
                $message .= ", {$ticket->room->name}";
            }
        } else {
            $message .= "Не указано";
        }

        $message .= "\n\n👤 <b>Исполнитель:</b> ";
        if ($ticket->assigned_to_id) {
            $message .= $ticket->assignedTo->name;
        } else {
            $message .= "Не назначен";
        }

        $message .= "\n\n📅 <b>Создано:</b> " . $ticket->created_at->format("d.m.Y H:i");

        // Добавляем кнопки действий
        $message .= "\n\n🔧 <b>Действия:</b>\n";

        if ($ticket->status !== "in_progress" && $user->canManageTickets()) {
            $message .= "▶️ <code>/start_ticket_{$ticket->id}</code> - Взять в работу\n";
        }

        if (!$ticket->assigned_to_id && $user->canManageTickets()) {
            $message .= "👤 <code>/assign_{$ticket->id}</code> - Назначить себе\n";
        }

        if ($ticket->status === "in_progress" && $ticket->assigned_to_id === $user->id && $user->canManageTickets()) {
            $message .= "✅ <code>/resolve_{$ticket->id}</code> - Отметить решенной\n";
        }

        return $this->telegramService->sendMessage($chatId, $message);
    }

    /**
     * Обрабатывает команду взятия заявки в работу
     */
    public function handleStartTicket(int $chatId, int $ticketId): bool
    {
        $user = $this->authService->getAuthenticatedUser($chatId);
        if (!$user) {
            return $this->sendAuthRequired($chatId);
        }

        if (!$user->canManageTickets()) {
            $message = "❌ У вас нет прав для взятия заявок в работу.";
            return $this->telegramService->sendMessage($chatId, $message);
        }

        $ticket = Ticket::find($ticketId);
        if (!$ticket) {
            $message = "❌ Заявка с ID {$ticketId} не найдена.";
            return $this->telegramService->sendMessage($chatId, $message);
        }

        if ($ticket->status === "closed") {
            $message = "❌ Нельзя взять в работу закрытую заявку.";
            return $this->telegramService->sendMessage($chatId, $message);
        }

        if ($ticket->status === "in_progress") {
            $message = "❌ Заявка уже находится в работе.";
            return $this->telegramService->sendMessage($chatId, $message);
        }

        try {
            $oldStatus = $ticket->status;
            $ticket->update([
                'status' => 'in_progress',
                'assigned_to_id' => $user->id
            ]);

            // Добавляем комментарий
            $ticket->comments()->create([
                'user_id' => $user->id,
                'content' => "Заявка взята в работу и назначена на {$user->name}",
                'is_system' => true
            ]);

            $message = "✅ <b>Заявка #{$ticket->id} успешно взята в работу!</b>\n\n";
            $message .= "📋 <b>Название:</b> {$ticket->title}\n";
            $message .= "👤 <b>Назначена на:</b> {$user->name}\n";
            $message .= "📊 <b>Статус:</b> " . $this->getStatusEmoji('in_progress') . " В работе";

            Log::info('Ticket started successfully', [
                'chat_id' => $chatId,
                'ticket_id' => $ticketId,
                'user_id' => $user->id,
                'old_status' => $oldStatus,
                'new_status' => 'in_progress'
            ]);

            return $this->telegramService->sendMessage($chatId, $message);
        } catch (\Exception $e) {
            Log::error('Error starting ticket', [
                'chat_id' => $chatId,
                'ticket_id' => $ticketId,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            $message = "❌ Произошла ошибка при взятии заявки в работу. Попробуйте еще раз.";
            return $this->telegramService->sendMessage($chatId, $message);
        }
    }

    /**
     * Обрабатывает команду назначения заявки
     */
    public function handleAssignTicket(int $chatId, int $ticketId): bool
    {
        $user = $this->authService->getAuthenticatedUser($chatId);
        if (!$user) {
            return $this->sendAuthRequired($chatId);
        }

        if (!$user->canManageTickets()) {
            $message = "❌ У вас нет прав для назначения заявок.";
            return $this->telegramService->sendMessage($chatId, $message);
        }

        $ticket = Ticket::find($ticketId);
        if (!$ticket) {
            $message = "❌ Заявка с ID {$ticketId} не найдена.";
            return $this->telegramService->sendMessage($chatId, $message);
        }

        if ($ticket->status === "closed") {
            $message = "❌ Нельзя назначить исполнителя на закрытую заявку.";
            return $this->telegramService->sendMessage($chatId, $message);
        }

        if ($ticket->assigned_to_id === $user->id) {
            $message = "ℹ️ Заявка #{$ticket->id} уже назначена на вас.";
            return $this->telegramService->sendMessage($chatId, $message);
        }

        $ticket->update(['assigned_to_id' => $user->id]);

        // Добавляем комментарий
        $ticket->comments()->create([
            'user_id' => $user->id,
            'content' => "Заявка назначена на {$user->name}",
            'is_system' => true
        ]);

        $message = "✅ <b>Заявка #{$ticket->id} успешно назначена на вас!</b>\n\n";
        $message .= "📋 <b>Название:</b> {$ticket->title}\n";
        $message .= "👤 <b>Назначена на:</b> {$user->name}";

        return $this->telegramService->sendMessage($chatId, $message);
    }

    /**
     * Обрабатывает команду отметки заявки как решенной
     */
    public function handleResolveTicket(int $chatId, int $ticketId): bool
    {
        $user = $this->authService->getAuthenticatedUser($chatId);
        if (!$user) {
            return $this->sendAuthRequired($chatId);
        }

        if (!$user->canManageTickets()) {
            $message = "❌ У вас нет прав для выполнения этого действия.";
            return $this->telegramService->sendMessage($chatId, $message);
        }

        $ticket = Ticket::find($ticketId);
        if (!$ticket) {
            $message = "❌ Заявка с ID {$ticketId} не найдена.";
            return $this->telegramService->sendMessage($chatId, $message);
        }

        if ($ticket->assigned_to_id !== $user->id) {
            $message = "❌ Только назначенный исполнитель может отметить заявку как решенную.";
            return $this->telegramService->sendMessage($chatId, $message);
        }

        if ($ticket->status !== "in_progress") {
            $message = "❌ Только заявки в статусе 'В работе' могут быть отмечены как решенные.";
            return $this->telegramService->sendMessage($chatId, $message);
        }

        $ticket->update(['status' => 'resolved']);

        // Добавляем комментарий
        $ticket->comments()->create([
            'user_id' => $user->id,
            'content' => "Заявка отмечена как решенная",
            'is_system' => true
        ]);

        $message = "✅ <b>Заявка #{$ticket->id} успешно отмечена как решенная!</b>\n\n";
        $message .= "📋 <b>Название:</b> {$ticket->title}\n";
        $message .= "👤 <b>Решена:</b> {$user->name}\n";
        $message .= "📊 <b>Статус:</b> " . $this->getStatusEmoji('resolved') . " Решена\n\n";
        $message .= "⏳ Дождитесь подтверждения от заявителя.";

        return $this->telegramService->sendMessage($chatId, $message);
    }

    /**
     * Отправляет сообщение о необходимости авторизации
     */
    protected function sendAuthRequired(int $chatId): bool
    {
        $message = "🔐 <b>Требуется авторизация</b>\n\n";
        $message .= "Для выполнения этой команды необходимо авторизоваться.\n\n";
        $message .= "Отправьте <code>/login</code> для входа в систему.";
        
        return $this->telegramService->sendMessage($chatId, $message);
    }

    /**
     * Получает эмодзи для статуса
     */
    protected function getStatusEmoji(string $status): string
    {
        return match ($status) {
            'new' => '🆕',
            'in_progress' => '🔄',
            'resolved' => '✅',
            'closed' => '🔒',
            default => '❓'
        };
    }

    /**
     * Получает человекочитаемый статус
     */
    protected function getHumanReadableStatus(string $status): string
    {
        return match ($status) {
            'new' => 'Новая',
            'in_progress' => 'В работе',
            'resolved' => 'Решена',
            'closed' => 'Закрыта',
            default => $status
        };
    }

    /**
     * Получает эмодзи для приоритета
     */
    protected function getPriorityEmoji(string $priority): string
    {
        return match (strtolower($priority)) {
            'low' => '🟢',
            'medium' => '🟡',
            'high' => '🟠',
            'critical' => '🔴',
            default => '❓'
        };
    }
}
