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

    
     * Обрабатывает команду /start

    public function handleStart(int $chatId): bool
    {
        $message = "👋 <b>Добро пожаловать в систему управления заявками!</b>\n\n";
        $message .= "Для начала работы необходимо авторизоваться.\n\n";
        $message .= "📋 <b>Доступные команды:</b>\n";
        $message .= "• <code>/login</code> - Авторизация\n";
        $message .= "• <code>/help</code> - Справка";

        return $this->telegramService->sendMessage($chatId, $message);
    }

    
     * Обрабатывает команду /help

    public function handleHelp(int $chatId): bool
    {
        $message = "📋 <b>Справка по командам</b>\n\n";
        
        if ($this->authService->isUserAuthenticated($chatId)) {
            $user = $this->authService->getAuthenticatedUser($chatId);
            $message .= "🔐 <b>Авторизованные команды:</b>\n";
            $message .= "• <code>/tickets</code> - Список активных заявок\n";
            $message .= "• <code>/all_tickets</code> - Все заявки (включая закрытые)\n";
            $message .= "• <code>/active</code> - Активные заявки в работе\n";
            $message .= "• <code>/stats</code> - Статистика заявок\n";
            $message .= "• <code>/ticket_123</code> - Подробности заявки 
            $message .= "• <code>/start_ticket_123</code> - Взять заявку 
            $message .= "• <code>/assign_123</code> - Назначить заявку 
            $message .= "• <code>/resolve_123</code> - Отметить заявку 
            $message .= "• <code>/close_123</code> - Закрыть заявку 
            
            
            if ($user->isAdmin() || $user->isMaster()) {
                $message .= "• <code>/rooms</code> - Список помещений\n";
                $message .= "• <code>/equipment</code> - Список оборудования\n";
                $message .= "• <code>/users</code> - Список пользователей\n";
            }
            
            $message .= "• <code>/logout</code> - Выйти из системы\n\n";
            
            
            $message .= "💡 <b>Что делать дальше?</b>\n";
            $message .= "1️⃣ Используйте <code>/tickets</code> для просмотра активных заявок\n";
            $message .= "2️⃣ Выберите заявку и используйте <code>/ticket_ID</code> для подробностей\n";
            $message .= "3️⃣ Взять заявку в работу: <code>/start_ticket_ID</code>\n";
            $message .= "4️⃣ Отметить как решенную: <code>/resolve_ID</code>\n\n";
        } else {
            $message .= "🔓 <b>Команды без авторизации:</b>\n";
            $message .= "• <code>/login</code> - Авторизация в системе\n\n";
        }
        
        $message .= "📞 <b>Общие команды:</b>\n";
        $message .= "• <code>/start</code> - Начать работу с ботом\n";
        $message .= "• <code>/help</code> - Показать эту справку";

        return $this->telegramService->sendMessage($chatId, $message);
    }

    
     * Обрабатывает команду /tickets

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

        
        if ($user->isAdmin() || $user->isMaster()) {
            $tickets = Ticket::where('status', '!=', 'closed')
                ->orderBy('created_at', 'desc')
                ->take(20)
                ->get();
        } else {
            $tickets = Ticket::where('status', '!=', 'closed')
                ->where(function ($query) use ($user) {
                    $query->where('assigned_to_id', $user->id)
                          ->orWhereNull('assigned_to_id');
                })
                ->orderBy('created_at', 'desc')
                ->take(20)
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

            $message .= "🆔 <b>
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

    
     * Обрабатывает команду /all_tickets

    public function handleAllTickets(int $chatId): bool
    {
        $user = $this->authService->getAuthenticatedUser($chatId);
        if (!$user) {
            return $this->sendAuthRequired($chatId);
        }

        if (!$user->canManageTickets()) {
            $message = "❌ У вас нет прав для просмотра заявок.";
            return $this->telegramService->sendMessage($chatId, $message);
        }

        
        if ($user->isAdmin() || $user->isMaster()) {
            $tickets = Ticket::orderBy('created_at', 'desc')
                ->take(30)
                ->get();
        } else {
            $tickets = Ticket::where(function ($query) use ($user) {
                $query->where('assigned_to_id', $user->id)
                      ->orWhereNull('assigned_to_id');
            })
            ->orderBy('created_at', 'desc')
            ->take(30)
            ->get();
        }

        if ($tickets->isEmpty()) {
            $message = "📋 Заявок не найдено.";
            return $this->telegramService->sendMessage($chatId, $message);
        }

        $message = "📋 <b>Все заявки:</b>\n\n";

        foreach ($tickets as $ticket) {
            $status = $this->getStatusEmoji($ticket->status) . " " . $this->getHumanReadableStatus($ticket->status);
            $priority = $this->getPriorityEmoji($ticket->priority) . " " . ucfirst($ticket->priority);

            $message .= "🆔 <b>
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

        $message .= "💡 Используйте <code>/tickets</code> для просмотра только активных заявок";

        return $this->telegramService->sendMessage($chatId, $message);
    }

    
     * Обрабатывает команду /active

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
            
            $message .= "🆔 <b>
            $message .= "⚡ Приоритет: {$priority}\n";
            
            if ($ticket->assignedTo) {
                $message .= "👤 Исполнитель: {$ticket->assignedTo->name}\n";
            }
            
            $message .= "📅 Взята в работу: " . $ticket->updated_at->format("d.m.Y H:i") . "\n";
            $message .= "📝 Заявитель: {$ticket->reporter_name}\n";
            
            
            if ($ticket->assigned_to_id === $user->id) {
                $message .= "✅ <code>/resolve_{$ticket->id}</code> - Отметить решенной\n";
            }
            $message .= "🔍 <code>/ticket_{$ticket->id}</code> - Подробнее\n\n";
        }

        $message .= "💡 Используйте <code>/tickets</code> для просмотра всех заявок";

        return $this->telegramService->sendMessage($chatId, $message);
    }

    
     * Обрабатывает команду /stats

    public function handleStats(int $chatId): bool
    {
        $user = $this->authService->getAuthenticatedUser($chatId);
        if (!$user) {
            return $this->sendAuthRequired($chatId);
        }

        if (!$user->canManageTickets()) {
            $message = "❌ У вас нет прав для просмотра статистики.";
            return $this->telegramService->sendMessage($chatId, $message);
        }

        
        if ($user->isAdmin() || $user->isMaster()) {
            $allTickets = Ticket::all();
        } else {
            $allTickets = Ticket::where(function ($query) use ($user) {
                $query->where('assigned_to_id', $user->id)
                      ->orWhereNull('assigned_to_id');
            })->get();
        }

        $stats = [
            'total' => $allTickets->count(),
            'open' => $allTickets->where('status', 'open')->count(),
            'in_progress' => $allTickets->where('status', 'in_progress')->count(),
            'resolved' => $allTickets->where('status', 'resolved')->count(),
            'closed' => $allTickets->where('status', 'closed')->count(),
        ];

        $message = "📊 <b>Статистика заявок</b>\n\n";
        $message .= "📋 <b>Всего заявок:</b> {$stats['total']}\n";
        $message .= "🆕 <b>Открытых:</b> {$stats['open']}\n";
        $message .= "🔄 <b>В работе:</b> {$stats['in_progress']}\n";
        $message .= "✅ <b>Решенных:</b> {$stats['resolved']}\n";
        $message .= "🔒 <b>Закрытых:</b> {$stats['closed']}\n\n";

        
        if ($stats['total'] > 0) {
            $openPercent = round(($stats['open'] / $stats['total']) * 100);
            $inProgressPercent = round(($stats['in_progress'] / $stats['total']) * 100);
            $resolvedPercent = round(($stats['resolved'] / $stats['total']) * 100);
            $closedPercent = round(($stats['closed'] / $stats['total']) * 100);

            $message .= "📈 <b>Процентное соотношение:</b>\n";
            $message .= "🆕 Открытых: {$openPercent}%\n";
            $message .= "🔄 В работе: {$inProgressPercent}%\n";
            $message .= "✅ Решенных: {$resolvedPercent}%\n";
            $message .= "🔒 Закрытых: {$closedPercent}%";
        }

        return $this->telegramService->sendMessage($chatId, $message);
    }

    
     * Обрабатывает команду просмотра деталей заявки

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

        $message = "🔍 <b>Детали заявки 
        $message .= "📋 <b>Название:</b> {$ticket->title}\n";
        $message .= "📂 <b>Категория:</b> " . $this->getCategoryEmoji($ticket->category) . " " . $this->getHumanReadableCategory($ticket->category) . "\n";
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

    
     * Обрабатывает команду взятия заявки в работу

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

            
            $ticket->comments()->create([
                'user_id' => $user->id,
                'content' => "Заявка взята в работу и назначена на {$user->name}",
                'is_system' => true
            ]);

            $message = "✅ <b>Заявка 
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

    
     * Обрабатывает команду назначения заявки

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
            $message = "ℹ️ Заявка 
            return $this->telegramService->sendMessage($chatId, $message);
        }

        $ticket->update(['assigned_to_id' => $user->id]);

        
        $ticket->comments()->create([
            'user_id' => $user->id,
            'content' => "Заявка назначена на {$user->name}",
            'is_system' => true
        ]);

        $message = "✅ <b>Заявка 
        $message .= "📋 <b>Название:</b> {$ticket->title}\n";
        $message .= "👤 <b>Назначена на:</b> {$user->name}";

        return $this->telegramService->sendMessage($chatId, $message);
    }

    
     * Обрабатывает команду отметки заявки как решенной

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

        
        $ticket->comments()->create([
            'user_id' => $user->id,
            'content' => "Заявка отмечена как решенная",
            'is_system' => true
        ]);

        $message = "✅ <b>Заявка 
        $message .= "📋 <b>Название:</b> {$ticket->title}\n";
        $message .= "👤 <b>Решена:</b> {$user->name}\n";
        $message .= "📊 <b>Статус:</b> " . $this->getStatusEmoji('resolved') . " Решена\n\n";
        $message .= "⏳ Дождитесь подтверждения от заявителя.";

        return $this->telegramService->sendMessage($chatId, $message);
    }

    
     * Отправляет сообщение о необходимости авторизации

    protected function sendAuthRequired(int $chatId): bool
    {
        $message = "🔐 <b>Требуется авторизация</b>\n\n";
        $message .= "Для выполнения этой команды необходимо авторизоваться.\n\n";
        $message .= "Отправьте <code>/login</code> для входа в систему.";
        
        return $this->telegramService->sendMessage($chatId, $message);
    }

    
     * Получает эмодзи для статуса

    protected function getStatusEmoji(string $status): string
    {
        return match ($status) {
            'open' => '🆕',
            'in_progress' => '🔄',
            'resolved' => '✅',
            'closed' => '🔒',
            default => '❓'
        };
    }

    
     * Получает человекочитаемый статус

    protected function getHumanReadableStatus(string $status): string
    {
        return match ($status) {
            'open' => 'Открыта',
            'in_progress' => 'В работе',
            'resolved' => 'Решена',
            'closed' => 'Закрыта',
            default => $status
        };
    }

    
     * Получает эмодзи для приоритета

    protected function getPriorityEmoji(string $priority): string
    {
        return match (strtolower($priority)) {
            'low' => '🟢',
            'medium' => '🟡',
            'high' => '🟠',
            'urgent' => '🔴',
            default => '❓'
        };
    }

    
     * Получает эмодзи для категории

    protected function getCategoryEmoji(string $category): string
    {
        return match (strtolower($category)) {
            'hardware' => '💻',
            'software' => '💿',
            'network' => '🌐',
            'account' => '👤',
            'other' => '📋',
            default => '❓'
        };
    }

    
     * Получает человекочитаемую категорию

    protected function getHumanReadableCategory(string $category): string
    {
        return match (strtolower($category)) {
            'hardware' => 'Оборудование',
            'software' => 'Программное обеспечение',
            'network' => 'Сеть и интернет',
            'account' => 'Учетная запись',
            'other' => 'Другое',
            default => $category
        };
    }

    
     * Обрабатывает команду закрытия заявки

    public function handleCloseTicket(int $chatId, int $ticketId): bool
    {
        $user = $this->authService->getAuthenticatedUser($chatId);
        if (!$user) {
            return $this->sendAuthRequired($chatId);
        }

        if (!$user->canManageTickets()) {
            $message = "❌ У вас нет прав для закрытия заявок.";
            return $this->telegramService->sendMessage($chatId, $message);
        }

        $ticket = Ticket::find($ticketId);
        if (!$ticket) {
            $message = "❌ Заявка с ID {$ticketId} не найдена.";
            return $this->telegramService->sendMessage($chatId, $message);
        }

        if ($ticket->status === "closed") {
            $message = "❌ Заявка уже закрыта.";
            return $this->telegramService->sendMessage($chatId, $message);
        }

        if ($ticket->status !== "resolved") {
            $message = "❌ Только решенные заявки могут быть закрыты.";
            return $this->telegramService->sendMessage($chatId, $message);
        }

        $ticket->update(['status' => 'closed']);

        
        $ticket->comments()->create([
            'user_id' => $user->id,
            'content' => "Заявка закрыта",
            'is_system' => true
        ]);

        $message = "🔒 <b>Заявка 
        $message .= "📋 <b>Название:</b> {$ticket->title}\n";
        $message .= "👤 <b>Закрыта:</b> {$user->name}\n";
        $message .= "📊 <b>Статус:</b> " . $this->getStatusEmoji('closed') . " Закрыта\n\n";
        $message .= "✅ Заявка полностью завершена.";

        return $this->telegramService->sendMessage($chatId, $message);
    }

    
     * Обрабатывает команду /rooms

    public function handleRooms(int $chatId): bool
    {
        $user = $this->authService->getAuthenticatedUser($chatId);
        if (!$user) {
            return $this->sendAuthRequired($chatId);
        }

        if (!$user->isAdmin() && !$user->isMaster()) {
            $message = "❌ У вас нет прав для просмотра помещений.";
            return $this->telegramService->sendMessage($chatId, $message);
        }

        $rooms = \App\Models\Room::active()
            ->with('location')
            ->orderBy('number')
            ->take(20)
            ->get();

        if ($rooms->isEmpty()) {
            $message = "🏢 Помещений не найдено.";
            return $this->telegramService->sendMessage($chatId, $message);
        }

        $message = "🏢 <b>Список помещений:</b>\n\n";

        foreach ($rooms as $room) {
            $message .= "🏢 <b>{$room->number}</b> - {$room->name}\n";
            $message .= "📍 <b>Местоположение:</b> " . ($room->location ? $room->location->name : 'Не указано') . "\n";
            $message .= "🏗️ <b>Тип:</b> {$room->type}\n";
            if ($room->building) {
                $message .= "🏢 <b>Здание:</b> {$room->building}\n";
            }
            if ($room->floor) {
                $message .= "🏢 <b>Этаж:</b> {$room->floor}\n";
            }
            $message .= "\n";
        }

        $message .= "💡 Используйте <code>/equipment</code> для просмотра оборудования";

        return $this->telegramService->sendMessage($chatId, $message);
    }

    
     * Обрабатывает команду /equipment

    public function handleEquipment(int $chatId): bool
    {
        $user = $this->authService->getAuthenticatedUser($chatId);
        if (!$user) {
            return $this->sendAuthRequired($chatId);
        }

        if (!$user->isAdmin() && !$user->isMaster()) {
            $message = "❌ У вас нет прав для просмотра оборудования.";
            return $this->telegramService->sendMessage($chatId, $message);
        }

        $equipment = \App\Models\Equipment::with(['room', 'category', 'status'])
            ->orderBy('inventory_number')
            ->take(20)
            ->get();

        if ($equipment->isEmpty()) {
            $message = "💻 Оборудования не найдено.";
            return $this->telegramService->sendMessage($chatId, $message);
        }

        $message = "💻 <b>Список оборудования:</b>\n\n";

        foreach ($equipment as $item) {
            $message .= "💻 <b>{$item->inventory_number}</b> - {$item->name}\n";
            $message .= "📂 <b>Категория:</b> " . ($item->category ? $item->category->name : 'Не указана') . "\n";
            $message .= "📊 <b>Статус:</b> " . ($item->status ? $item->status->name : 'Не указан') . "\n";
            if ($item->room) {
                $message .= "🏢 <b>Помещение:</b> {$item->room->number} - {$item->room->name}\n";
            }
            if ($item->model) {
                $message .= "🔧 <b>Модель:</b> {$item->model}\n";
            }
            $message .= "\n";
        }

        $message .= "💡 Используйте <code>/rooms</code> для просмотра помещений";

        return $this->telegramService->sendMessage($chatId, $message);
    }

    
     * Обрабатывает команду /users

    public function handleUsers(int $chatId): bool
    {
        $user = $this->authService->getAuthenticatedUser($chatId);
        if (!$user) {
            return $this->sendAuthRequired($chatId);
        }

        if (!$user->isAdmin() && !$user->isMaster()) {
            $message = "❌ У вас нет прав для просмотра пользователей.";
            return $this->telegramService->sendMessage($chatId, $message);
        }

        $users = \App\Models\User::with('role')
            ->where('is_active', true)
            ->orderBy('name')
            ->take(20)
            ->get();

        if ($users->isEmpty()) {
            $message = "👥 Пользователей не найдено.";
            return $this->telegramService->sendMessage($chatId, $message);
        }

        $message = "👥 <b>Список пользователей:</b>\n\n";

        foreach ($users as $userItem) {
            $role = $userItem->role ? $userItem->role->name : 'Без роли';
            $status = $userItem->is_active ? '✅ Активен' : '❌ Заблокирован';
            
            $message .= "👤 <b>{$userItem->name}</b>\n";
            $message .= "📧 <b>Email:</b> {$userItem->email}\n";
            $message .= "📞 <b>Телефон:</b> {$userItem->phone}\n";
            $message .= "👔 <b>Роль:</b> {$role}\n";
            $message .= "📊 <b>Статус:</b> {$status}\n\n";
        }

        $message .= "💡 Используйте <code>/stats</code> для просмотра статистики";

        return $this->telegramService->sendMessage($chatId, $message);
    }
}
