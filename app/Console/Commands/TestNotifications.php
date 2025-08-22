<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class TestNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "notifications:test {--user-id= : ID пользователя для тестирования}";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Тестирование системы уведомлений";

    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("🔔 Тестирование системы уведомлений...");
        $this->newLine();

        // Получаем пользователя для тестирования
        $userId = $this->option("user-id");

        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("Пользователь с ID {$userId} не найден!");
                return 1;
            }
        } else {
            $user = User::whereHas("role", function ($q) {
                $q->whereIn("slug", ["admin", "master", "technician"]);
            })->first();

            if (!$user) {
                $this->error(
                    "Не найдено пользователей с ролями admin, master или technician!",
                );
                return 1;
            }
        }

        $this->info(
            "Тестируем уведомления для пользователя: {$user->name} ({$user->email})",
        );
        $this->newLine();

        // Тест 1: Создание тестовой заявки
        $this->info("1. Создание тестовой заявки...");
        $ticket = $this->createTestTicket($user);
        $this->line("   ✅ Создана заявка #{$ticket->id}: {$ticket->title}");

        // Тест 2: Уведомление о новой заявке
        $this->info("2. Отправка уведомления о новой заявке...");
        $this->notificationService->notifyNewTicket($ticket);
        $this->line("   ✅ Уведомления отправлены");

        // Тест 3: Изменение статуса
        $this->info("3. Изменение статуса заявки...");
        $this->notificationService->notifyTicketStatusChanged(
            $ticket,
            "open",
            "in_progress",
        );
        $ticket->update(["status" => "in_progress"]);
        $this->line('   ✅ Статус изменен на "В работе"');

        // Тест 4: Назначение исполнителя
        $this->info("4. Назначение исполнителя...");
        $technician = User::whereHas("role", function ($q) {
            $q->where("slug", "technician");
        })->first();

        if ($technician) {
            $this->notificationService->notifyTicketAssigned(
                $ticket,
                $technician,
            );
            $ticket->update(["assigned_to_id" => $technician->id]);
            $this->line(
                "   ✅ Заявка назначена пользователю: {$technician->name}",
            );
        } else {
            $this->warn("   ⚠️  Не найдено техников для назначения");
        }

        // Тест 5: Проверка уведомлений пользователя
        $this->info("5. Проверка уведомлений пользователя...");
        $notifications = $this->notificationService->getUserNotifications(
            $user,
        );
        $this->line("   📧 Всего уведомлений: {$notifications->count()}");

        $unreadCount = $this->notificationService->getUnreadCount($user);
        $this->line("   🔔 Непрочитанных: {$unreadCount}");

        // Отображение последних уведомлений
        if ($notifications->count() > 0) {
            $this->newLine();
            $this->info("Последние уведомления:");
            $this->table(
                ["Тип", "Заголовок", "Сообщение", "Время"],
                $notifications
                    ->take(5)
                    ->map(function ($notification) {
                        return [
                            $notification["type"],
                            $notification["title"],
                            \Str::limit($notification["message"], 50),
                            $notification["created_at"],
                        ];
                    })
                    ->toArray(),
            );
        }

        // Тест 6: Статистика уведомлений
        $this->info("6. Статистика уведомлений...");
        $stats = $this->notificationService->getNotificationStats($user);
        $this->line(
            "   📊 Всего: {$stats["total"]}, Непрочитанных: {$stats["unread"]}, За неделю: {$stats["recent"]}",
        );

        // Очистка тестовых данных (опционально)
        if ($this->confirm("Удалить тестовую заявку?", true)) {
            $ticket->delete();
            $this->line("   🗑️  Тестовая заявка удалена");
        }

        $this->newLine();
        $this->info("✅ Тестирование завершено успешно!");

        return 0;
    }

    /**
     * Создание тестовой заявки
     */
    private function createTestTicket(User $user): Ticket
    {
        return Ticket::create([
            "title" => "Тестовая заявка - " . now()->format("d.m.Y H:i:s"),
            "description" =>
                "Это тестовая заявка для проверки системы уведомлений.",
            "category" => "testing",
            "priority" => "medium",
            "status" => "open",
            "reporter_name" => $user->name,
            "reporter_email" => $user->email,
            "user_id" => $user->id,
        ]);
    }
}
