<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use App\Services\TelegramNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TelegramBot extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'telegram:bot {--mode=polling : Bot mode (polling|webhook)}';

    /**
     * The console command description.
     */
    protected $description = 'Run Telegram bot in polling or webhook mode';

    protected TelegramService $telegramService;
    protected TelegramNotificationService $notificationService;
    protected int $lastUpdateId = 0;

    public function __construct(
        TelegramService $telegramService,
        TelegramNotificationService $notificationService
    ) {
        parent::__construct();
        $this->telegramService = $telegramService;
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $mode = $this->option('mode');

        $this->info("Starting Telegram bot in {$mode} mode...");

        // Проверяем подключение к API
        if (!$this->testConnection()) {
            $this->error('Failed to connect to Telegram API');
            return 1;
        }

        if ($mode === 'polling') {
            return $this->runPolling();
        } elseif ($mode === 'webhook') {
            return $this->setupWebhook();
        } else {
            $this->error('Invalid mode. Use "polling" or "webhook"');
            return 1;
        }
    }

    /**
     * Запускает бота в режиме long polling
     */
    protected function runPolling(): int
    {
        $this->info('Starting long polling...');
        $this->info('Bot is listening. Press Ctrl+C to stop.');

        // Удаляем webhook для использования polling
        $this->telegramService->deleteWebhook();

        while (true) {
            try {
                // Получаем новые обновления
                $updates = $this->telegramService->getUpdates($this->lastUpdateId + 1, 30);

                if (!empty($updates)) {
                    foreach ($updates as $update) {
                        $this->lastUpdateId = max($this->lastUpdateId, $update['update_id']);
                        $this->processUpdate($update);
                    }
                }

                // Проверяем новые заявки каждые 15 секунд
                $this->checkNewTickets();

                // Небольшая пауза
                sleep(1);
            } catch (\Exception $e) {
                $this->error('Error in polling loop: ' . $e->getMessage());
                Log::error('Telegram bot polling error', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                sleep(5);
            }
        }

        // Этот код никогда не выполнится, но нужен для статического анализа
        return 0;
    }

    /**
     * Настраивает webhook
     */
    protected function setupWebhook(): int
    {
        $webhookUrl = config('app.url') . '/api/telegram/webhook';
        
        $this->info("Setting up webhook: {$webhookUrl}");

        if ($this->telegramService->setWebhook($webhookUrl)) {
            $this->info('Webhook set successfully');
            
            // Проверяем информацию о webhook
            $webhookInfo = $this->telegramService->getWebhookInfo();
            if ($webhookInfo) {
                $this->info('Webhook info: ' . json_encode($webhookInfo, JSON_PRETTY_PRINT));
            }

            // Запускаем проверку новых заявок в фоне
            $this->info('Starting background ticket checking...');
            $this->runBackgroundTicketChecking();
        } else {
            $this->error('Failed to set webhook');
            return 1;
        }

        return 0;
    }

    /**
     * Запускает проверку новых заявок в фоне
     */
    protected function runBackgroundTicketChecking(): void
    {
        $this->info('Background ticket checking started. Press Ctrl+C to stop.');

        while (true) {
            try {
                $this->checkNewTickets();
                sleep(15); // Проверяем каждые 15 секунд
            } catch (\Exception $e) {
                $this->error('Error in background ticket checking: ' . $e->getMessage());
                Log::error('Telegram bot background checking error', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                sleep(30);
            }
        }
    }

    /**
     * Проверяет новые заявки и отправляет уведомления
     */
    protected function checkNewTickets(): void
    {
        try {
            $this->info('Checking for new tickets...');
            $this->notificationService->notifyNewTickets();
            $this->info('New tickets check completed');
        } catch (\Exception $e) {
            $this->error('Error checking new tickets: ' . $e->getMessage());
            Log::error('Error checking new tickets', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Обрабатывает полученное обновление
     */
    protected function processUpdate(array $update): void
    {
        // Обрабатываем только сообщения
        if (!isset($update['message'])) {
            return;
        }

        $message = $update['message'];
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';
        $from = $message['from'];

        $username = $from['username'] ?? ($from['first_name'] ?? 'Unknown');

        $this->info("Received message from @{$username}: {$text}");

        // Обрабатываем сообщение через TelegramController
        try {
            $controller = app(\App\Http\Controllers\TelegramController::class);
            
            // Создаем фейковый Request объект
            $request = new \Illuminate\Http\Request();
            $request->merge($update);
            
            // Обрабатываем сообщение
            $controller->webhook($request);
            
        } catch (\Exception $e) {
            $this->error('Error processing message: ' . $e->getMessage());
            Log::error('Error processing Telegram message', [
                'chat_id' => $chatId,
                'text' => $text,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Проверяет подключение к Telegram API
     */
    protected function testConnection(): bool
    {
        $this->info('Testing connection to Telegram API...');

        $botInfo = $this->telegramService->getBotInfo();
        
        if ($botInfo) {
            $this->info('✅ Connection successful!');
            $this->info("Bot: @{$botInfo['username']} ({$botInfo['first_name']})");
            return true;
        } else {
            $this->error('❌ Connection failed!');
            return false;
        }
    }
}
