<?php

namespace App\Console\Commands;

use App\Http\Controllers\TelegramBotController;
use Illuminate\Console\Command;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Cache\LaravelCache;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Telegram\TelegramDriver;
use Illuminate\Support\Facades\Log;

class TelegramLongPolling extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "telegram:polling";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Start long polling for Telegram bot";

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting Telegram bot long polling...");

        try {
            // Проверяем загрузку драйвера
            $this->info("Loading Telegram driver...");
            DriverManager::loadDriver(TelegramDriver::class);
            $this->info("Telegram driver loaded successfully");

            // Проверяем наличие токена
            $token = config("services.telegram.token");
            $this->info(
                "Using token: " .
                    (empty($token) ? "EMPTY!" : substr($token, 0, 5) . "..."),
            );

            if (empty($token)) {
                $this->error(
                    "Telegram bot token is not set. Please add TELEGRAM_BOT_TOKEN to your .env file.",
                );
                return 1;
            }

            // Проверяем доступность API Telegram
            $this->info("Testing Telegram API connection...");
            $response = \Illuminate\Support\Facades\Http::get(
                "https://api.telegram.org/bot{$token}/getMe",
            );
            $result = $response->json();

            if (
                $response->successful() &&
                isset($result["ok"]) &&
                $result["ok"]
            ) {
                $botInfo = $result["result"];
                $this->info(
                    "Connected to Telegram bot: @" .
                        $botInfo["username"] .
                        " (ID: " .
                        $botInfo["id"] .
                        ")",
                );
            } else {
                $this->error(
                    "Could not connect to Telegram API: " .
                        ($result["description"] ?? "Unknown error"),
                );
                $this->info("Full response: " . json_encode($result));
                return 1;
            }

            $config = [
                "telegram" => [
                    "token" => $token,
                ],
            ];

            $botman = BotManFactory::create($config, new LaravelCache());

            // Получаем контроллер бота, чтобы настроить все обработчики
            $controller = app(TelegramBotController::class);

            // Очищаем webhook перед запуском long polling
            $this->info("Removing any existing webhook...");
            $response = \Illuminate\Support\Facades\Http::get(
                "https://api.telegram.org/bot{$token}/deleteWebhook",
            );
            $result = $response->json();

            if (
                $response->successful() &&
                isset($result["ok"]) &&
                $result["ok"]
            ) {
                $this->info("Webhook successfully removed.");
            } else {
                $this->warn(
                    "Could not remove webhook: " .
                        ($result["description"] ?? "Unknown error"),
                );
            }

            $this->info("Setting up bot conversations...");
            $controller->setupConversations($botman);

            $this->info("Bot is listening. Press Ctrl+C to stop.");

            // Запускаем бесконечный цикл для long polling с дополнительной отладкой
            $this->info("Starting listening loop...");
            $iteration = 0;

            while (true) {
                try {
                    $iteration++;
                    $this->info(
                        "Iteration #{$iteration}: Waiting for messages...",
                    );

                    // Получаем обновления напрямую для отладки
                    $updatesResponse = \Illuminate\Support\Facades\Http::get(
                        "https://api.telegram.org/bot{$token}/getUpdates",
                        ["timeout" => 30, "offset" => -1, "limit" => 5],
                    );

                    $updates = $updatesResponse->json();
                    if (
                        $updatesResponse->successful() &&
                        isset($updates["ok"]) &&
                        $updates["ok"]
                    ) {
                        $count = count($updates["result"] ?? []);
                        $this->info("Found {$count} pending updates");

                        if ($count > 0) {
                            $this->info(
                                "Updates data: " .
                                    json_encode($updates["result"]),
                            );
                        }
                    } else {
                        $this->warn(
                            "Failed to check updates: " .
                                ($updates["description"] ?? "Unknown error"),
                        );
                    }

                    // Стандартное прослушивание через BotMan
                    $botman->listen();

                    // Добавляем небольшую паузу, чтобы не нагружать сервер
                    $this->info("Sleeping for 2 seconds before next check...");
                    sleep(2);
                } catch (\Exception $e) {
                    Log::error(
                        "Error in bot listening loop: " . $e->getMessage(),
                    );
                    $this->error("Error: " . $e->getMessage());
                    $this->error("Stack trace: " . $e->getTraceAsString());
                    // Пауза перед повторной попыткой
                    $this->info("Sleeping for 5 seconds before retry...");
                    sleep(5);
                }
            }
        } catch (\Exception $e) {
            Log::error(
                "Critical error in telegram:polling command: " .
                    $e->getMessage(),
            );
            $this->error("Critical error: " . $e->getMessage());
            return 1;
        }
    }
}
