<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramDeleteWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:delete-webhook';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Удаляет webhook для Telegram бота';

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

        $this->info("Удаляем webhook для Telegram бота...");

        try {
            // Отправляем запрос на удаление webhook
            $response = Http::get("https://api.telegram.org/bot$token/deleteWebhook");

            $result = $response->json();

            if ($response->successful() && isset($result['ok']) && $result['ok']) {
                $this->info('Webhook успешно удален!');
                return 0;
            } else {
                $this->error('Не удалось удалить webhook: ' . ($result['description'] ?? 'Неизвестная ошибка'));
                Log::error('Ошибка удаления webhook для Telegram: ' . json_encode($result));
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('Произошла ошибка при удалении webhook: ' . $e->getMessage());
            Log::error('Исключение при удалении webhook для Telegram: ' . $e->getMessage());
            return 1;
        }
    }
}
