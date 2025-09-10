<?php

/**
 * Тестовый скрипт для проверки запросов заявок
 */

require_once 'vendor/autoload.php';

use App\Models\Ticket;
use App\Models\User;

echo "🔍 Тестирование запросов заявок\n\n";

try {
    // Тест 1: Проверка общего количества заявок
    echo "1. Общее количество заявок:\n";
    $totalTickets = Ticket::count();
    echo "   📋 Всего заявок: {$totalTickets}\n\n";

    // Тест 2: Проверка заявок по статусам
    echo "2. Заявки по статусам:\n";
    $statuses = ['open', 'in_progress', 'resolved', 'closed'];
    
    foreach ($statuses as $status) {
        $count = Ticket::where('status', $status)->count();
        echo "   📊 {$status}: {$count}\n";
    }
    echo "\n";

    // Тест 3: Проверка активных заявок (не закрытых)
    echo "3. Активные заявки (не закрытые):\n";
    $activeTickets = Ticket::where('status', '!=', 'closed')->count();
    echo "   🔄 Активных заявок: {$activeTickets}\n\n";

    // Тест 4: Проверка заявок в работе
    echo "4. Заявки в работе:\n";
    $inProgressTickets = Ticket::where('status', 'in_progress')->count();
    echo "   ⚡ В работе: {$inProgressTickets}\n\n";

    // Тест 5: Проверка последних заявок
    echo "5. Последние 10 заявок:\n";
    $recentTickets = Ticket::orderBy('created_at', 'desc')->take(10)->get();
    
    foreach ($recentTickets as $ticket) {
        $status = $ticket->status;
        $emoji = match ($status) {
            'open' => '🆕',
            'in_progress' => '🔄',
            'resolved' => '✅',
            'closed' => '🔒',
            default => '❓'
        };
        
        echo "   {$emoji} #{$ticket->id}: {$ticket->title} ({$status})\n";
    }
    echo "\n";

    // Тест 6: Проверка пользователей с правами
    echo "6. Пользователи с правами на заявки:\n";
    $users = User::whereHas('role', function ($query) {
        $query->whereIn('slug', ['admin', 'master', 'technician']);
    })->get();
    
    echo "   👥 Пользователей с правами: {$users->count()}\n";
    
    foreach ($users as $user) {
        $role = $user->role->slug ?? 'unknown';
        $telegramId = $user->telegram_id ? '✅' : '❌';
        echo "   👤 {$user->name} ({$role}) - Telegram: {$telegramId}\n";
    }
    echo "\n";

    // Тест 7: Проверка заявок с назначенными исполнителями
    echo "7. Заявки с назначенными исполнителями:\n";
    $assignedTickets = Ticket::whereNotNull('assigned_to_id')->count();
    $unassignedTickets = Ticket::whereNull('assigned_to_id')->count();
    
    echo "   👤 Назначенных: {$assignedTickets}\n";
    echo "   ❓ Неназначенных: {$unassignedTickets}\n\n";

    echo "✅ Все тесты завершены успешно!\n\n";
    
    echo "📋 Рекомендации:\n";
    if ($totalTickets == 0) {
        echo "   ⚠️  В системе нет заявок. Создайте тестовые заявки.\n";
    }
    
    if ($users->where('telegram_id', '!=', null)->count() == 0) {
        echo "   ⚠️  У пользователей нет Telegram ID. Настройте авторизацию в боте.\n";
    }
    
    if ($activeTickets > 20) {
        echo "   💡 В системе много активных заявок ({$activeTickets}). Бот покажет только 20.\n";
    }
    
    if ($activeTickets > 30) {
        echo "   💡 В системе очень много активных заявок ({$activeTickets}). Используйте /all_tickets для просмотра всех.\n";
    }

} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    echo "📍 Файл: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
