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

        // Обрабатываем сообщение напрямую
        try {
            $this->info("Processing message directly");
            $this->processMessageDirectly($chatId, $text);
            $this->info("Message processed successfully");
            
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
     * Обрабатывает сообщение напрямую
     */
    protected function processMessageDirectly(int $chatId, string $text): void
    {
        $authService = app(\App\Services\TelegramAuthService::class);
        $commandService = app(\App\Services\TelegramCommandService::class);
        
        Log::info('Processing message directly', [
            'chat_id' => $chatId,
            'text' => $text,
            'in_auth_process' => $authService->isUserInAuthProcess($chatId)
        ]);

        // Проверяем, находится ли пользователь в процессе авторизации
        if ($authService->isUserInAuthProcess($chatId)) {
            Log::info('User in auth process, processing auth message', ['chat_id' => $chatId]);
            $this->processAuthMessageDirectly($chatId, $text, $authService);
            return;
        }

        // Обрабатываем команды
        if (strpos($text, '/') === 0) {
            Log::info('Processing command', ['chat_id' => $chatId, 'command' => $text]);
            $this->processCommandDirectly($chatId, $text, $commandService);
            return;
        }

        // Обычные сообщения
        Log::info('Processing unknown message', ['chat_id' => $chatId, 'text' => $text]);
        $this->handleUnknownMessageDirectly($chatId, $text);
    }

    /**
     * Обрабатывает сообщения в процессе авторизации
     */
    protected function processAuthMessageDirectly(int $chatId, string $text, $authService): void
    {
        $authState = $authService->getAuthState($chatId);
        
        Log::info('Processing auth message directly', [
            'chat_id' => $chatId,
            'text' => $text,
            'auth_state' => $authState
        ]);
        
        if (!$authState) {
            Log::warning('No auth state found', ['chat_id' => $chatId]);
            return;
        }

        switch ($authState['step']) {
            case 'phone':
                Log::info('Processing phone step', ['chat_id' => $chatId]);
                $authService->processPhone($chatId, $text);
                break;
            case 'password':
                Log::info('Processing password step', ['chat_id' => $chatId]);
                $authService->processPassword($chatId, $text);
                break;
        }
    }

    /**
     * Обрабатывает команды напрямую
     */
    protected function processCommandDirectly(int $chatId, string $text, $commandService): void
    {
        $command = strtolower(trim($text));

        // Обрабатываем команды с параметрами
        if (preg_match('/^\/ticket_(\d+)$/', $command, $matches)) {
            $ticketId = (int) $matches[1];
            $commandService->handleTicketDetails($chatId, $ticketId);
            return;
        }

        if (preg_match('/^\/start_ticket_(\d+)$/', $command, $matches)) {
            $ticketId = (int) $matches[1];
            $commandService->handleStartTicket($chatId, $ticketId);
            return;
        }

        if (preg_match('/^\/assign_(\d+)$/', $command, $matches)) {
            $ticketId = (int) $matches[1];
            $commandService->handleAssignTicket($chatId, $ticketId);
            return;
        }

        if (preg_match('/^\/resolve_(\d+)$/', $command, $matches)) {
            $ticketId = (int) $matches[1];
            $commandService->handleResolveTicket($chatId, $ticketId);
            return;
        }

        if (preg_match('/^\/close_(\d+)$/', $command, $matches)) {
            $ticketId = (int) $matches[1];
            $commandService->handleCloseTicket($chatId, $ticketId);
            return;
        }

        // Обрабатываем простые команды
        switch ($command) {
            case '/start':
                $commandService->handleStart($chatId);
                break;
            case '/help':
                $commandService->handleHelp($chatId);
                break;
            case '/login':
                $authService = app(\App\Services\TelegramAuthService::class);
                $authService->startAuth($chatId);
                break;
            case '/logout':
                $authService = app(\App\Services\TelegramAuthService::class);
                $authService->logout($chatId);
                break;
            case '/tickets':
                $commandService->handleTickets($chatId);
                break;
            case '/all_tickets':
                $commandService->handleAllTickets($chatId);
                break;
            case '/active':
                $commandService->handleActive($chatId);
                break;
            case '/stats':
                $commandService->handleStats($chatId);
                break;
            case '/rooms':
                $commandService->handleRooms($chatId);
                break;
            case '/equipment':
                $commandService->handleEquipment($chatId);
                break;
            case '/users':
                $commandService->handleUsers($chatId);
                break;
            case '/reset_auth':
                $authService = app(\App\Services\TelegramAuthService::class);
                $authService->resetAuthBlock($chatId);
                break;
            default:
                $this->handleUnknownCommandDirectly($chatId, $command);
                break;
        }
    }

    /**
     * Обрабатывает неизвестные команды
     */
    protected function handleUnknownCommandDirectly(int $chatId, string $command): void
    {
        $message = "❓ <b>Неизвестная команда</b>\n\n";
        $message .= "Команда <code>{$command}</code> не распознана.\n\n";
        $message .= "Отправьте <code>/help</code> для получения списка доступных команд.";

        $telegramService = app(\App\Services\TelegramService::class);
        $telegramService->sendMessage($chatId, $message);
    }

    /**
     * Обрабатывает неизвестные сообщения
     */
    protected function handleUnknownMessageDirectly(int $chatId, string $text): void
    {
        $message = "🤔 <b>Не понимаю</b>\n\n";
        $message .= "Отправьте <code>/help</code> для получения списка доступных команд.";

        $telegramService = app(\App\Services\TelegramService::class);
        $telegramService->sendMessage($chatId, $message);
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
