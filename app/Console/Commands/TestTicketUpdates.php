<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestTicketUpdates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "test:ticket-updates {--create-tickets=3} {--test-api}";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Тестирование системы обновления заявок на главной странице";

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
        $this->info("🔄 Тестирование системы обновления заявок");
        $this->newLine();

        // Тестируем API endpoint
        if ($this->option("test-api")) {
            $this->testHomeApi();
        }

        // Создаем тестовые заявки
        $ticketCount = (int) $this->option("create-tickets");
        if ($ticketCount > 0) {
            $this->createTestTickets($ticketCount);
        }

        $this->newLine();
        $this->info("✅ Тестирование завершено!");
        $this->newLine();

        $this->comment("📋 Инструкции для тестирования в браузере:");
        $this->line("1. Откройте главную страницу в браузере");
        $this->line(
            "2. Авторизуйтесь как пользователь с правами техника/админа",
        );
        $this->line("3. Откройте консоль браузера (F12)");
        $this->line(
            "4. Наблюдайте за автоматическим обновлением каждые 30 секунд",
        );
        $this->line(
            "5. Создайте новую заявку и проверьте, появится ли она на главной",
        );

        return 0;
    }

    private function testHomeApi()
    {
        $this->info("🌐 Тестирование API endpoint...");

        try {
            $baseUrl = config("app.url", "http://localhost");
            $url = "{$baseUrl}/home/technician/tickets";

            $this->line("   URL: {$url}");

            // Проверяем доступность endpoint'а
            $response = Http::get($url);

            if ($response->successful()) {
                $data = $response->json();
                $this->line("   ✅ API работает");
                $this->line(
                    "   📊 Заявок в ответе: " . count($data["tickets"] ?? []),
                );
                $this->line(
                    "   📈 Статистика: " . json_encode($data["stats"] ?? []),
                );
            } else {
                $this->error("   ❌ API вернул ошибку: {$response->status()}");
                if ($response->status() === 403) {
                    $this->warn(
                        "   💡 Возможно, нужно авторизоваться в браузере",
                    );
                }
            }
        } catch (\Exception $e) {
            $this->error(
                "   ❌ Ошибка при тестировании API: " . $e->getMessage(),
            );
        }
    }

    private function createTestTickets(int $count)
    {
        $this->info("🎫 Создание {$count} тестовых заявок...");

        // Найдем пользователя для создания заявок
        $user = User::first();
        if (!$user) {
            $this->error("   ❌ Не найдено ни одного пользователя в системе");
            return;
        }

        $priorities = ["low", "medium", "high", "urgent"];
        $categories = ["hardware", "software", "network", "other"];

        for ($i = 1; $i <= $count; $i++) {
            $ticket = Ticket::create([
                "title" =>
                    "Тестовая заявка #{$i} - " . now()->format("d.m.Y H:i:s"),
                "description" =>
                    "Это тестовая заявка #{$i}, созданная для проверки системы автообновления на главной странице. Время создания: " .
                    now()->toDateTimeString(),
                "category" => $categories[array_rand($categories)],
                "priority" => $priorities[array_rand($priorities)],
                "status" => "open",
                "reporter_name" => $user->name,
                "reporter_email" => $user->email,
                "user_id" => $user->id,
            ]);

            // Отправляем уведомления
            $this->notificationService->notifyNewTicket($ticket);

            $this->line(
                "   ✅ Создана заявка #{$ticket->id}: {$ticket->title}",
            );

            // Небольшая задержка между созданием заявок
            if ($i < $count) {
                sleep(1);
            }
        }

        $this->line("   🔔 Уведомления отправлены техникам");
    }
}
