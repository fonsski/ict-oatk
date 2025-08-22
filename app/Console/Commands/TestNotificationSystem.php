<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class TestNotificationSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "test:notifications {--create-ticket} {--user-id=}";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Тестирование системы уведомлений";

    protected $notificationService;

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
        $this->info("🔔 Тестирование системы уведомлений");
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
                    "Не найдено пользователей с правами получения уведомлений!",
                );
                return 1;
            }
        }

        $this->info("Используем пользователя: {$user->name} ({$user->email})");
        $this->newLine();

        // Тестируем API endpoint для получения количества уведомлений
        $this->info("1. Тестируем получение текущих уведомлений...");
        $currentCount = $this->notificationService->getUnreadCount($user);
        $this->line("   ✅ Непрочитанных уведомлений: {$currentCount}");

        // Создаем тестовое уведомление напрямую
        $this->info("2. Создаем тестовое уведомление...");
        $this->createTestNotification($user);
        $this->line("   ✅ Тестовое уведомление создано");

        // Проверяем, увеличилось ли количество
        $newCount = $this->notificationService->getUnreadCount($user);
        $this->line("   ✅ Новое количество непрочитанных: {$newCount}");

        // Создаем тестовую заявку, если указана опция
        if ($this->option("create-ticket")) {
            $this->info("3. Создаем тестовую заявку...");
            $ticket = $this->createTestTicket($user);
            $this->line(
                "   ✅ Создана заявка #{$ticket->id}: {$ticket->title}",
            );

            // Отправляем уведомление о заявке
            $this->notificationService->notifyNewTicket($ticket);
            $this->line("   ✅ Уведомления о заявке отправлены");
        }

        // Показываем инструкции для тестирования в браузере
        $this->newLine();
        $this->info("🌐 Инструкции для тестирования в браузере:");
        $this->line("1. Откройте приложение в браузере");
        $this->line(
            "2. Авторизуйтесь как пользователь с правами техника/админа",
        );
        $this->line(
            "3. Обратите внимание на иконку уведомлений в правом верхнем углу",
        );
        $this->line(
            "4. Бейдж должен показывать количество непрочитанных уведомлений",
        );
        $this->line("5. Кликните по иконке, чтобы открыть список уведомлений");

        $this->newLine();
        $this->info("🔗 Полезные URL для тестирования API:");
        $baseUrl = config("app.url", "http://localhost");
        $this->line("- GET {$baseUrl}/api/notifications/unread-count");
        $this->line("- GET {$baseUrl}/api/notifications");
        $this->line("- GET {$baseUrl}/api/notifications/poll");

        $this->newLine();
        $this->comment(
            "💡 Совет: Откройте браузерские инструменты разработчика (F12) и перейдите на вкладку Network, чтобы видеть AJAX запросы в реальном времени.",
        );

        return 0;
    }

    private function createTestNotification(User $user)
    {
        // Создаем уведомление напрямую через сервис
        $this->notificationService->createNotification([
            "user_id" => $user->id,
            "type" => "test",
            "title" => "Тестовое уведомление",
            "message" =>
                "Это тестовое уведомление для проверки системы. Время: " .
                now()->format("H:i:s"),
            "data" => [
                "test" => true,
                "created_by_command" => true,
            ],
            "url" => route("home"),
        ]);
    }

    private function createTestTicket(User $user): Ticket
    {
        return Ticket::create([
            "title" => "Тестовая заявка - " . now()->format("d.m.Y H:i:s"),
            "description" =>
                "Это тестовая заявка, созданная командой для проверки системы уведомлений.",
            "category" => "testing",
            "priority" => "medium",
            "status" => "open",
            "reporter_name" => $user->name,
            "reporter_email" => $user->email,
            "user_id" => $user->id,
        ]);
    }
}
