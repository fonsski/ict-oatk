<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramBasicTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:basic-test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test basic Telegram API functionality without BotMan';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting basic Telegram API test...');

        // Получаем токен из конфигурации
        $token = config('services.telegram.token');

        if (empty($token)) {
            $this->error('Telegram bot token is not set. Please add TELEGRAM_BOT_TOKEN to your .env file.');
            return 1;
        }

        $this->info('Using token: ' . substr($token, 0, 5) . '...');

        // Тест 1: Проверка подключения к API (метод getMe)
        $this->info('Test 1: Testing connection to Telegram API...');

        try {
            $response = Http::get("https://api.telegram.org/bot{$token}/getMe");
            $result = $response->json();

            if ($response->successful() && isset($result['ok']) && $result['ok']) {
                $botInfo = $result['result'];
                $this->info('✅ Connection successful!');
                $this->info('Bot information:');
                $this->info('- Username: @' . $botInfo['username']);
                $this->info('- ID: ' . $botInfo['id']);
                $this->info('- Name: ' . $botInfo['first_name']);
            } else {
                $this->error('❌ Connection failed!');
                $this->error('Error: ' . ($result['description'] ?? 'Unknown error'));
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('❌ Exception when connecting to Telegram API: ' . $e->getMessage());
            return 1;
        }

        // Тест 2: Проверка наличия обновлений
        $this->info('Test 2: Checking for updates...');

        try {
            $response = Http::get("https://api.telegram.org/bot{$token}/getUpdates", [
                'limit' => 10,
                'timeout' => 5
            ]);

            $result = $response->json();

            if ($response->successful() && isset($result['ok']) && $result['ok']) {
                $updates = $result['result'];
                $count = count($updates);

                $this->info('✅ Successfully retrieved updates!');
                $this->info("Found {$count} updates");

                if ($count > 0) {
                    $this->info('Latest updates:');
                    foreach ($updates as $index => $update) {
                        $updateId = $update['update_id'];
                        $messageText = $update['message']['text'] ?? '(no text)';
                        $fromUsername = $update['message']['from']['username'] ?? 'unknown';

                        $this->info("- Update #{$updateId} from @{$fromUsername}: \"{$messageText}\"");
                    }
                }
            } else {
                $this->error('❌ Failed to retrieve updates!');
                $this->error('Error: ' . ($result['description'] ?? 'Unknown error'));
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('❌ Exception when getting updates: ' . $e->getMessage());
            return 1;
        }

        // Тест 3: Отправка тестового сообщения
        $this->info('Test 3: Would you like to send a test message? (yes/no)');
        $sendMessage = $this->ask('Enter yes or no:');

        if (strtolower($sendMessage) === 'yes') {
            $chatId = $this->ask('Enter the chat ID to send a message to:');

            try {
                $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => 'Test message from Laravel Telegram Basic Test (' . now() . ')',
                    'parse_mode' => 'Markdown'
                ]);

                $result = $response->json();

                if ($response->successful() && isset($result['ok']) && $result['ok']) {
                    $this->info('✅ Message sent successfully!');
                } else {
                    $this->error('❌ Failed to send message!');
                    $this->error('Error: ' . ($result['description'] ?? 'Unknown error'));
                }
            } catch (\Exception $e) {
                $this->error('❌ Exception when sending message: ' . $e->getMessage());
            }
        }

        $this->info('Basic Telegram API test completed!');
        return 0;
    }
}
