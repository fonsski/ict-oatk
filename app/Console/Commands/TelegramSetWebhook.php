<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramSetWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:set-webhook {url?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Устанавливает webhook для Telegram бота';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $token = config('services.telegram.token');

        if (empty($token)) {
            $this->error('Токен Telegram бота не указан. Добавьте TELEGRAM_BOT_TOKEN в .env файл.');
            return 1;
        }

        // Получаем URL из аргумента или генерируем его из APP_URL
        $url = $this->argument('url');
        if (empty($url)) {
            $appUrl = config('app.url');
            if (empty($appUrl)) {
                $this->error('URL приложения не указан. Добавьте APP_URL в .env файл или укажите URL как аргумент команды.');
                return 1;
            }
            $url = rtrim($appUrl, '/') . '/api/telegram/webhook';
        }

        $this->info("Настраиваем webhook для Telegram бота на URL: $url");

        try {
            // Отправляем запрос на установку webhook
            $response = Http::get("https://api.telegram.org/bot$token/setWebhook", [
                'url' => $url,
                'allowed_updates' => json_encode([
                    'message', 'callback_query', 'inline_query'
                ]),
            ]);

            $result = $response->json();

            if ($response->successful() && isset($result['ok']) && $result['ok']) {
                $this->info('Webhook успешно установлен!');
                return 0;
            } else {
                $this->error('Не удалось установить webhook: ' . ($result['description'] ?? 'Неизвестная ошибка'));
                Log::error('Ошибка установки webhook для Telegram: ' . json_encode($result));
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('Произошла ошибка при настройке webhook: ' . $e->getMessage());
            Log::error('Исключение при установке webhook для Telegram: ' . $e->getMessage());
            return 1;
        }
    }
}
