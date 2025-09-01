<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Ticket;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class TelegramStandalone extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "telegram:standalone";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Run Telegram bot in standalone mode without BotMan";

    /**
     * @var string
     */
    protected $token;

    /**
     * @var int
     */
    protected $lastUpdateId = 0;

    /**
     * @var array
     */
    protected $notifiedTickets = [];

    /**
     * @var int
     */
    protected $lastCheckTime = 0;

    /**
     * @var bool
     */
    protected $isCheckingTickets = false;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting Telegram bot in standalone mode...");

        // Получаем токен из конфигурации
        $this->token = config("services.telegram.token");

        if (empty($this->token)) {
            $this->error(
                "Telegram bot token is not set. Please add TELEGRAM_BOT_TOKEN to your .env file.",
            );
            return 1;
        }

        $this->info("Using token: " . substr($this->token, 0, 5) . "...");

        // Проверяем подключение к API
        $this->testConnection();

        // Очищаем webhook
        $this->removeWebhook();

        // Запускаем цикл обработки сообщений
        $this->startPolling();

        return 0;
    }

    /**
     * Проверяет подключение к API Telegram
     */
    protected function testConnection()
    {
        $this->info("Testing connection to Telegram API...");

        try {
            $response = Http::get(
                "https://api.telegram.org/bot{$this->token}/getMe",
            );
            $result = $response->json();

            if (
                $response->successful() &&
                isset($result["ok"]) &&
                $result["ok"]
            ) {
                $botInfo = $result["result"];
                $this->info("✅ Connection successful!");
                $this->info(
                    "Bot: @" .
                        $botInfo["username"] .
                        " (" .
                        $botInfo["first_name"] .
                        ")",
                );
                return true;
            } else {
                $this->error("❌ Connection failed!");
                $this->error(
                    "Error: " . ($result["description"] ?? "Unknown error"),
                );
                return false;
            }
        } catch (\Exception $e) {
            $this->error(
                "❌ Exception when connecting to Telegram API: " .
                    $e->getMessage(),
            );
            return false;
        }
    }

    /**
     * Удаляет webhook для использования long polling
     */
    protected function removeWebhook()
    {
        $this->info("Removing any existing webhook...");

        try {
            $response = Http::get(
                "https://api.telegram.org/bot{$this->token}/deleteWebhook",
            );
            $result = $response->json();

            if (
                $response->successful() &&
                isset($result["ok"]) &&
                $result["ok"]
            ) {
                $this->info("✅ Webhook successfully removed.");
                return true;
            } else {
                $this->warn(
                    "⚠️ Could not remove webhook: " .
                        ($result["description"] ?? "Unknown error"),
                );
                return false;
            }
        } catch (\Exception $e) {
            $this->error(
                "❌ Exception when removing webhook: " . $e->getMessage(),
            );
            return false;
        }
    }

    /**
     * Запускает цикл обработки сообщений
     */
    protected function startPolling()
    {
        $this->info("Starting message polling loop...");
        $this->info("Bot is listening. Press Ctrl+C to stop.");

        while (true) {
            try {
                // Получаем новые сообщения
                $updates = $this->getUpdates();

                if (!empty($updates)) {
                    foreach ($updates as $update) {
                        // Обновляем последний ID обновления
                        $this->lastUpdateId = max(
                            $this->lastUpdateId,
                            $update["update_id"],
                        );

                        // Обрабатываем сообщение
                        $this->processUpdate($update);
                    }
                }

                // Проверяем, прошло ли 15 секунд с момента последней проверки новых заявок
                $currentTime = time();
                if (
                    $currentTime - $this->lastCheckTime >= 15 &&
                    !$this->isCheckingTickets
                ) {
                    $this->isCheckingTickets = true;

                    // Загружаем сохраненный список уведомленных заявок
                    $savedNotifiedTickets = cache()->get(
                        "telegram_notified_tickets",
                        [],
                    );
                    if (
                        is_array($savedNotifiedTickets) &&
                        !empty($savedNotifiedTickets)
                    ) {
                        $this->notifiedTickets = $savedNotifiedTickets;
                        $this->info(
                            "Загружен список уведомленных заявок из кеша: " .
                                count($this->notifiedTickets),
                        );
                    }

                    $this->checkForNewTickets();
                    $this->lastCheckTime = $currentTime;
                    $this->isCheckingTickets = false;
                }

                // Пауза перед следующим запросом
                sleep(1);
            } catch (\Exception $e) {
                $this->error("Error in polling loop: " . $e->getMessage());
                Log::error(
                    "Telegram standalone bot error: " . $e->getMessage(),
                );
                // Пауза перед повторной попыткой
                sleep(5);
            }
        }
    }

    /**
     * Получает новые обновления от API Telegram
     */
    protected function getUpdates()
    {
        $params = [
            "timeout" => 30,
            "limit" => 100,
        ];

        // Если есть последний ID обновления, запрашиваем только новые
        if ($this->lastUpdateId > 0) {
            $params["offset"] = $this->lastUpdateId + 1;
        }

        $response = Http::get(
            "https://api.telegram.org/bot{$this->token}/getUpdates",
            $params,
        );
        $result = $response->json();

        if ($response->successful() && isset($result["ok"]) && $result["ok"]) {
            $updates = $result["result"] ?? [];

            // Обновляем lastUpdateId даже если нет обновлений, чтобы избежать дублирования
            if (!empty($updates)) {
                $this->lastUpdateId = max(array_column($updates, "update_id"));
                $this->info("Новый lastUpdateId: " . $this->lastUpdateId);
            }

            return $updates;
        }

        return [];
    }

    /**
     * Обрабатывает полученное обновление
     */
    protected function processUpdate($update)
    {
        // Обрабатываем только сообщения
        if (!isset($update["message"])) {
            return;
        }

        $message = $update["message"];
        $chatId = $message["chat"]["id"];
        $text = $message["text"] ?? "";
        $from = $message["from"];

        $username = $from["username"] ?? ($from["first_name"] ?? "Unknown");

        $this->info("Received message from @{$username}: {$text}");

        // Обрабатываем команды
        if (strpos($text, "/") === 0) {
            $command = strtolower(trim($text));

            // Проверяем, есть ли пробел в команде (например, "/resolve 1")
            if (strpos($command, " ") !== false) {
                $parts = explode(" ", $command);
                $baseCommand = $parts[0];
                $parameter = $parts[1] ?? null;

                if ($parameter && is_numeric($parameter)) {
                    if ($baseCommand === "/ticket") {
                        $this->handleTicketDetailsCommand($chatId, $parameter);
                        return;
                    } elseif ($baseCommand === "/start_ticket") {
                        $this->handleStartTicketCommand($chatId, $parameter);
                        return;
                    } elseif ($baseCommand === "/assign") {
                        $this->handleAssignTicketCommand($chatId, $parameter);
                        return;
                    } elseif ($baseCommand === "/resolve") {
                        $this->handleResolveTicketCommand($chatId, $parameter);
                        return;
                    }
                }
            }

            $command = strtolower(explode(" ", $text)[0]);

            // Проверка на команды с параметрами (например, /ticket_1)
            if (preg_match('/^\/ticket_(\d+)$/', $command, $matches)) {
                $ticketId = $matches[1];
                $this->handleTicketDetailsCommand($chatId, $ticketId);
                return;
            } elseif (
                preg_match('/^\/start_ticket_(\d+)$/', $command, $matches)
            ) {
                $ticketId = $matches[1];
                $this->handleStartTicketCommand($chatId, $ticketId);
                return;
            } elseif (preg_match('/^\/assign_(\d+)$/', $command, $matches)) {
                $ticketId = $matches[1];
                $this->handleAssignTicketCommand($chatId, $ticketId);
                return;
            } elseif (preg_match('/^\/resolve_(\d+)$/', $command, $matches)) {
                $ticketId = $matches[1];
                $this->handleResolveTicketCommand($chatId, $ticketId);
                return;
            } elseif (
                preg_match('/^\/resolve[\s_](\d+)$/', $command, $matches)
            ) {
                $ticketId = $matches[1];
                $this->handleResolveTicketCommand($chatId, $ticketId);
                return;
            }

            switch ($command) {
                case "/start":
                    $this->handleStartCommand($chatId);
                    break;
                case "/help":
                    $this->handleHelpCommand($chatId);
                    break;
                case "/login":
                    $this->handleLoginCommand($chatId);
                    break;
                case "/tickets":
                    $this->handleTicketsCommand($chatId);
                    break;
                case "/resolve":
                    $this->sendMessage(
                        $chatId,
                        "Укажите ID заявки. Например: /resolve_1",
                    );
                    break;
                default:
                    $this->sendMessage(
                        $chatId,
                        "Неизвестная команда. Отправьте /help для получения списка доступных команд.",
                    );
                    break;
            }
        } else {
            // Проверяем, находится ли пользователь в процессе авторизации
            $authState = Cache::get("telegram_auth_{$chatId}");

            if ($authState) {
                $this->continueAuthProcess($chatId, $text, $authState);
            } else {
                $this->sendMessage(
                    $chatId,
                    "Отправьте /help для получения списка доступных команд.",
                );
            }
        }
    }

    /**
     * Обрабатывает команду /start
     */
    protected function handleStartCommand($chatId)
    {
        $message = "👋 Добро пожаловать в систему управления заявками!\n\n";
        $message .=
            "Для начала работы вам нужно авторизоваться. Отправьте команду /login для входа.";

        $this->sendMessage($chatId, $message);
    }

    /**
     * Обрабатывает команду /help
     */
    protected function handleHelpCommand($chatId)
    {
        $help = "Доступные команды:\n\n";
        $help .= "/login - Войти в систему\n";
        $help .= "/tickets - Показать список текущих заявок\n";
        $help .= "/ticket_{id} - Показать подробную информацию о заявке\n";
        $help .= "/start_ticket_{id} - Взять заявку в работу\n";
        $help .= "/assign_{id} - Назначить заявку себе\n";
        $help .= "/resolve_{id} - Отметить заявку решённой\n";
        $help .= "/logout - Выйти из системы\n";
        $help .= "/help - Показать эту справку";

        $this->sendMessage($chatId, $help);
    }

    /**
     * Обрабатывает команду /login
     */
    protected function handleLoginCommand($chatId)
    {
        // Сохраняем состояние авторизации
        Cache::put(
            "telegram_auth_{$chatId}",
            ["step" => "phone"],
            now()->addMinutes(15),
        );

        $message =
            "Для авторизации в системе необходимо ввести ваши учетные данные.\n\n";
        $message .= "Введите ваш номер телефона:";

        $this->sendMessage($chatId, $message);
    }

    /**
     * Продолжает процесс авторизации
     */
    protected function continueAuthProcess($chatId, $text, $authState)
    {
        if ($authState["step"] === "phone") {
            $phone = trim($text);

            // Очищаем номер телефона от форматирования
            $cleanPhone = preg_replace("/[^0-9]/", "", $phone);
            $this->info(
                "Попытка поиска пользователя по номеру: " . $cleanPhone,
            ); // Проверяем, существует ли пользователь с таким номером телефона
            $user = User::where(function ($query) use ($cleanPhone) {
                $query
                    ->where("phone", "like", "%" . $cleanPhone . "%")
                    ->orWhere(
                        "phone",
                        "like",
                        "%" . substr($cleanPhone, -10) . "%",
                    )
                    ->orWhere("phone", $cleanPhone);
            })->first();

            if ($user) {
                $this->info(
                    "Пользователь найден: " .
                        $user->name .
                        " (ID: " .
                        $user->id .
                        ")",
                );
                $this->info("Номер в базе: " . $user->phone);
            }

            if (!$user) {
                $this->sendMessage(
                    $chatId,
                    "Пользователь с таким номером телефона не найден. Попробуйте еще раз или отправьте /login для начала заново.",
                );
                Cache::forget("telegram_auth_{$chatId}");
                return;
            }

            // Проверяем, активен ли пользователь
            if (!$user->is_active) {
                $this->sendMessage(
                    $chatId,
                    "Ваша учетная запись неактивна. Обратитесь к администратору.",
                );
                Cache::forget("telegram_auth_{$chatId}");
                return;
            }

            // Переходим к вводу пароля
            Cache::put(
                "telegram_auth_{$chatId}",
                [
                    "step" => "password",
                    "phone" => $cleanPhone,
                ],
                now()->addMinutes(15),
            );

            $this->sendMessage($chatId, "Введите ваш пароль:");
        } elseif ($authState["step"] === "password") {
            $password = $text;
            $phone = $authState["phone"];

            // Выводим отладочную информацию
            $this->info("Попытка авторизации для телефона: " . $phone);

            // Находим пользователя напрямую для проверки пароля
            $user = User::where(function ($query) use ($phone) {
                $cleanPhone = preg_replace("/[^0-9]/", "", $phone);
                $query
                    ->where("phone", "like", "%" . $cleanPhone . "%")
                    ->orWhere(
                        "phone",
                        "like",
                        "%" . substr($cleanPhone, -10) . "%",
                    )
                    ->orWhere("phone", $cleanPhone);
            })
                ->where("is_active", true)
                ->first();

            if ($user && Hash::check($password, $user->password)) {
                $this->info(
                    "Авторизация успешна для пользователя: " . $user->name,
                );
                Auth::login($user);
                $user = Auth::user();

                // Сохраняем Telegram ID в профиле пользователя
                $user->update(["telegram_id" => $chatId]);

                // Сохраняем данные пользователя в кеше
                Cache::put(
                    "telegram_user_{$chatId}",
                    [
                        "user_id" => $user->id,
                        "authenticated_at" => now(),
                    ],
                    now()->addDays(30),
                );

                // Обновляем время последнего входа
                $user->updateLastLogin();

                // Удаляем состояние авторизации
                Cache::forget("telegram_auth_{$chatId}");

                // Отправляем приветственное сообщение
                $message = "👋 Здравствуйте, {$user->name}!\n\n";
                $message .=
                    "Вы успешно авторизовались в системе управления заявками.\n\n";
                $message .= "Доступные команды:\n";
                $message .= "/tickets - Показать список текущих заявок\n";
                $message .= "/help - Показать полный список команд";

                $this->sendMessage($chatId, $message);
            } else {
                $this->sendMessage(
                    $chatId,
                    "Неверный пароль. Попробуйте еще раз или отправьте /login для начала заново.",
                );
                $this->info(
                    "Неверный пароль для пользователя с телефоном: " . $phone,
                );
                if ($user) {
                    $this->info("Пользователь найден, но пароль не совпадает");
                } else {
                    $this->info("Пользователь не найден с указанным телефоном");
                }
                Cache::forget("telegram_auth_{$chatId}");
            }
        }
    }

    /**
     * Обрабатывает команду /tickets
     */
    protected function handleTicketsCommand($chatId)
    {
        // Проверяем авторизацию
        $userData = Cache::get("telegram_user_{$chatId}");

        if (!$userData || !isset($userData["user_id"])) {
            $this->sendMessage(
                $chatId,
                "Для выполнения этой команды необходимо авторизоваться. Отправьте /login для входа.",
            );
            return;
        }

        $user = User::find($userData["user_id"]);

        if (!$user) {
            $this->sendMessage(
                $chatId,
                "Ваша сессия устарела. Пожалуйста, авторизуйтесь снова с помощью команды /login.",
            );
            Cache::forget("telegram_user_{$chatId}");
            return;
        }

        if (!$user->canManageTickets()) {
            $this->sendMessage(
                $chatId,
                "У вас нет прав для просмотра списка заявок.",
            );
            return;
        }

        // Получаем список заявок в зависимости от роли пользователя
        if ($user->isAdmin() || $user->isMaster()) {
            $tickets = Ticket::where("status", "!=", "closed")
                ->orderBy("created_at", "desc")
                ->take(10)
                ->get();
        } else {
            $tickets = Ticket::where("status", "!=", "closed")
                ->where(function ($query) use ($user) {
                    $query
                        ->where("assigned_to_id", $user->id)
                        ->orWhereNull("assigned_to_id");
                })
                ->orderBy("created_at", "desc")
                ->take(10)
                ->get();
        }

        if ($tickets->isEmpty()) {
            $this->sendMessage($chatId, "Активных заявок не найдено.");
            return;
        }

        $reply = "📋 Список активных заявок:\n\n";

        foreach ($tickets as $ticket) {
            $status =
                $this->getStatusEmoji($ticket->status) .
                " " .
                $this->getHumanReadableStatus($ticket->status);
            $priority =
                $this->getPriorityEmoji($ticket->priority) .
                " " .
                ucfirst($ticket->priority);

            $reply .= "ID {$ticket->id}: {$ticket->title}\n";
            $reply .= "Статус: {$status}\n";
            $reply .= "Приоритет: {$priority}\n";

            if ($ticket->assigned_to_id) {
                $assignedTo = $ticket->assignedTo->name ?? "Неизвестно";
                $reply .= "Исполнитель: {$assignedTo}\n";
            } else {
                $reply .= "Исполнитель: Не назначен\n";
            }

            $reply .=
                "Создано: " . $ticket->created_at->format("d.m.Y H:i") . "\n";
            $reply .= "/ticket_{$ticket->id} - Подробнее\n\n";
        }

        $this->sendMessage($chatId, $reply);
    }

    /**
     * Проверяет наличие новых заявок
     */
    protected function checkForNewTickets()
    {
        $this->info("Checking for new tickets...");

        try {
            // Проверяем, инициализирован ли массив уведомленных заявок
            if (!is_array($this->notifiedTickets)) {
                $this->notifiedTickets = [];
                $this->info("Инициализирован массив уведомленных заявок");
            }

            // Получаем новые заявки, созданные за последние 15 минут
            $fifteenMinutesAgo = now()->subMinutes(15);

            $newTickets = Ticket::where("created_at", ">=", $fifteenMinutesAgo)
                ->whereNotIn("id", $this->notifiedTickets)
                ->get();

            if ($newTickets->isNotEmpty()) {
                $this->info("Found " . $newTickets->count() . " new tickets!");
                $this->info(
                    "Already notified tickets: " .
                        implode(", ", $this->notifiedTickets),
                );

                // Получаем список пользователей с Telegram ID
                $users = User::whereNotNull("telegram_id")
                    ->whereHas("role", function ($query) {
                        $query->whereIn("slug", [
                            "admin",
                            "master",
                            "technician",
                        ]);
                    })
                    ->get();

                if ($users->isEmpty()) {
                    $this->info("No users with Telegram ID to notify");
                    return;
                }

                foreach ($newTickets as $ticket) {
                    // Проверяем, не уведомляли ли мы уже об этой заявке
                    if (in_array($ticket->id, $this->notifiedTickets)) {
                        $this->info(
                            "Skipping already notified ticket #{$ticket->id}",
                        );
                        continue;
                    }

                    $this->info(
                        "Sending notification for ticket #{$ticket->id}",
                    );

                    $message = "🆕 Новая заявка #{$ticket->id}\n\n";
                    $message .= "Название: {$ticket->title}\n";
                    $message .= "Категория: {$ticket->category}\n";
                    $message .=
                        "Приоритет: " .
                        $this->getPriorityEmoji($ticket->priority) .
                        " " .
                        ucfirst($ticket->priority) .
                        "\n\n";
                    $message .= "Заявитель: {$ticket->reporter_name}\n\n";
                    $message .= "/ticket_{$ticket->id} - Подробнее";

                    foreach ($users as $user) {
                        $this->sendMessage($user->telegram_id, $message);
                    }

                    // Добавляем ID заявки в список уведомленных
                    $this->notifiedTickets[] = $ticket->id;
                }

                // Сохраняем список уведомленных заявок в кеш, чтобы избежать дублирования при перезапуске
                cache()->put(
                    "telegram_notified_tickets",
                    $this->notifiedTickets,
                    now()->addDays(1),
                );
            } else {
                $this->info("No new tickets found");
            }
        } catch (\Exception $e) {
            $this->error("Error checking for new tickets: " . $e->getMessage());
            Log::error("Error checking for new tickets: " . $e->getMessage());
        }
    }

    /**
     * Обработчик команды отметки заявки решённой
     */
    protected function handleResolveTicketCommand($chatId, $ticketId)
    {
        // Проверяем авторизацию
        $userData = Cache::get("telegram_user_{$chatId}");

        if (!$userData || !isset($userData["user_id"])) {
            $this->sendMessage(
                $chatId,
                "Для выполнения этой команды необходимо авторизоваться. Отправьте /login для входа.",
            );
            return;
        }

        $user = User::find($userData["user_id"]);

        if (!$user) {
            $this->sendMessage(
                $chatId,
                "Ваша сессия устарела. Пожалуйста, авторизуйтесь снова с помощью команды /login.",
            );
            Cache::forget("telegram_user_{$chatId}");
            return;
        }

        if (!$user->canManageTickets()) {
            $this->sendMessage(
                $chatId,
                "У вас нет прав для выполнения этого действия.",
            );
            return;
        }

        $ticket = Ticket::find($ticketId);

        if (!$ticket) {
            $this->sendMessage($chatId, "Заявка с ID {$ticketId} не найдена.");
            return;
        }

        // Проверяем, назначена ли заявка на текущего пользователя
        if ($ticket->assigned_to_id !== $user->id) {
            $this->sendMessage(
                $chatId,
                "Только назначенный исполнитель может отметить заявку как решённую.",
            );
            return;
        }

        // Проверяем, находится ли заявка в работе
        if ($ticket->status !== "in_progress") {
            $this->sendMessage(
                $chatId,
                "Только заявки в статусе 'В работе' могут быть отмечены как решённые.",
            );
            return;
        }

        $oldStatus = $ticket->status;
        $ticket->update(["status" => "resolved"]);

        // Добавляем комментарий о решении заявки
        $ticket->comments()->create([
            "user_id" => $user->id,
            "content" => "Заявка отмечена как решённая",
            "is_system" => true,
        ]);

        $this->sendMessage(
            $chatId,
            "✅ Заявка #{$ticket->id} успешно отмечена как решённая! Дождитесь подтверждения от заявителя.",
        );
    }

    /**
     * Экранирует специальные символы для Markdown
     */
    protected function escapeMarkdownChars($text)
    {
        $specialChars = [
            "_",
            "*",
            "[",
            "]",
            "(",
            ")",
            "~",
            "`",
            ">",
            "#",
            "+",
            "-",
            "=",
            "|",
            "{",
            "}",
            ".",
            "!",
        ];
        foreach ($specialChars as $char) {
            $text = str_replace($char, "\\" . $char, $text);
        }
        return $text;
    }

    /**
     * Обработчик команды просмотра деталей заявки
     */
    protected function handleTicketDetailsCommand($chatId, $ticketId)
    {
        // Проверяем авторизацию
        $userData = Cache::get("telegram_user_{$chatId}");

        if (!$userData || !isset($userData["user_id"])) {
            $this->sendMessage(
                $chatId,
                "Для выполнения этой команды необходимо авторизоваться. Отправьте /login для входа.",
            );
            return;
        }

        $user = User::find($userData["user_id"]);

        if (!$user) {
            $this->sendMessage(
                $chatId,
                "Ваша сессия устарела. Пожалуйста, авторизуйтесь снова с помощью команды /login.",
            );
            Cache::forget("telegram_user_{$chatId}");
            return;
        }

        // Проверяем, есть ли у пользователя права на просмотр заявок
        if (!$user->canManageTickets()) {
            $this->sendMessage($chatId, "У вас нет прав для просмотра заявок.");
            return;
        }

        $ticket = Ticket::find($ticketId);

        if (!$ticket) {
            $this->sendMessage($chatId, "Заявка с ID {$ticketId} не найдена.");
            return;
        }

        $status =
            $this->getStatusEmoji($ticket->status) .
            " " .
            $this->getHumanReadableStatus($ticket->status);
        $priority =
            $this->getPriorityEmoji($ticket->priority) .
            " " .
            ucfirst($ticket->priority);

        $reply = "🔍 Детали заявки #{$ticket->id}\n\n";
        $reply .= "Название: {$ticket->title}\n";
        $reply .= "Категория: {$ticket->category}\n";
        $reply .= "Статус: {$status}\n";
        $reply .= "Приоритет: {$priority}\n\n";

        $reply .= "Описание:\n{$ticket->description}\n\n";

        $reply .= "Заявитель: {$ticket->reporter_name}\n";
        if ($ticket->reporter_email) {
            $reply .= "Email: {$ticket->reporter_email}\n";
        }
        if ($ticket->reporter_phone) {
            $reply .= "Телефон: {$ticket->reporter_phone}\n";
        }

        $reply .= "\nМестоположение: ";
        if ($ticket->location) {
            $reply .= $ticket->location->name;
            if ($ticket->room) {
                $reply .= ", {$ticket->room->name}";
            }
        } else {
            $reply .= "Не указано";
        }

        $reply .= "\n\nИсполнитель: ";
        if ($ticket->assigned_to_id) {
            $reply .= $ticket->assignedTo->name;
        } else {
            $reply .= "Не назначен";
        }

        $reply .= "\n\nСоздано: " . $ticket->created_at->format("d.m.Y H:i");

        // Добавляем кнопки действий
        $reply .= "\n\nДействия:\n";

        if ($ticket->status !== "in_progress" && $user->canManageTickets()) {
            $reply .= "/start_ticket_{$ticket->id} - Взять в работу\n";
        }

        if (!$ticket->assigned_to_id && $user->canManageTickets()) {
            $reply .= "/assign_{$ticket->id} - Назначить себе\n";
        }

        if (
            $ticket->status === "in_progress" &&
            $ticket->assigned_to_id === $user->id &&
            $user->canManageTickets()
        ) {
            $reply .= "/resolve_{$ticket->id} - Отметить решённой\n";
        }

        $this->sendMessage($chatId, $reply);
    }

    /**
     * Обработчик команды взятия заявки в работу
     */
    protected function handleStartTicketCommand($chatId, $ticketId)
    {
        // Проверяем авторизацию
        $userData = Cache::get("telegram_user_{$chatId}");

        if (!$userData || !isset($userData["user_id"])) {
            $this->sendMessage(
                $chatId,
                "Для выполнения этой команды необходимо авторизоваться. Отправьте /login для входа.",
            );
            return;
        }

        $user = User::find($userData["user_id"]);

        if (!$user) {
            $this->sendMessage(
                $chatId,
                "Ваша сессия устарела. Пожалуйста, авторизуйтесь снова с помощью команды /login.",
            );
            Cache::forget("telegram_user_{$chatId}");
            return;
        }

        if (!$user->canManageTickets()) {
            $this->sendMessage(
                $chatId,
                "У вас нет прав для взятия заявок в работу.",
            );
            return;
        }

        $ticket = Ticket::find($ticketId);

        if (!$ticket) {
            $this->sendMessage($chatId, "Заявка с ID {$ticketId} не найдена.");
            return;
        }

        // Проверяем, не закрыта ли заявка
        if ($ticket->status === "closed") {
            $this->sendMessage(
                $chatId,
                "Нельзя взять в работу закрытую заявку.",
            );
            return;
        }

        // Проверяем, не в работе ли уже заявка
        if ($ticket->status === "in_progress") {
            $this->sendMessage($chatId, "Заявка уже находится в работе.");
            return;
        }

        $oldStatus = $ticket->status;
        // Обновляем статус и назначаем исполнителя
        $ticket->update([
            "status" => "in_progress",
            "assigned_to_id" => $user->id,
        ]);

        // Добавляем комментарий о смене статуса и назначении
        $ticket->comments()->create([
            "user_id" => $user->id,
            "content" => "Заявка взята в работу и назначена на {$user->name}",
            "is_system" => true,
        ]);

        $this->sendMessage(
            $chatId,
            "✅ Заявка #{$ticket->id} успешно взята в работу и назначена на вас!",
        );
    }

    /**
     * Обработчик команды назначения заявки себе
     */
    protected function handleAssignTicketCommand($chatId, $ticketId)
    {
        // Проверяем авторизацию
        $userData = Cache::get("telegram_user_{$chatId}");

        if (!$userData || !isset($userData["user_id"])) {
            $this->sendMessage(
                $chatId,
                "Для выполнения этой команды необходимо авторизоваться. Отправьте /login для входа.",
            );
            return;
        }

        $user = User::find($userData["user_id"]);

        if (!$user) {
            $this->sendMessage(
                $chatId,
                "Ваша сессия устарела. Пожалуйста, авторизуйтесь снова с помощью команды /login.",
            );
            Cache::forget("telegram_user_{$chatId}");
            return;
        }

        if (!$user->canManageTickets()) {
            $this->sendMessage(
                $chatId,
                "У вас нет прав для назначения заявок.",
            );
            return;
        }

        $ticket = Ticket::find($ticketId);

        if (!$ticket) {
            $this->sendMessage($chatId, "Заявка с ID {$ticketId} не найдена.");
            return;
        }

        // Проверяем, не закрыта ли заявка
        if ($ticket->status === "closed") {
            $this->sendMessage(
                $chatId,
                "Нельзя назначить исполнителя на закрытую заявку.",
            );
            return;
        }

        // Если заявка уже назначена на текущего пользователя
        if ($ticket->assigned_to_id === $user->id) {
            $this->sendMessage(
                $chatId,
                "Заявка #{$ticket->id} уже назначена на вас.",
            );
            return;
        }

        $oldAssignedId = $ticket->assigned_to_id;
        $ticket->update(["assigned_to_id" => $user->id]);

        // Добавляем комментарий о назначении
        $ticket->comments()->create([
            "user_id" => $user->id,
            "content" => "Заявка назначена на {$user->name}",
            "is_system" => true,
        ]);

        $this->sendMessage(
            $chatId,
            "✅ Заявка #{$ticket->id} успешно назначена на вас!",
        );
    }

    /**
     * Отправляет сообщение в чат
     */
    protected function sendMessage($chatId, $text, $markdown = false)
    {
        $params = [
            "chat_id" => $chatId,
            "text" => $text,
        ];

        if ($markdown) {
            $params["parse_mode"] = "HTML";
        }

        try {
            $response = Http::post(
                "https://api.telegram.org/bot{$this->token}/sendMessage",
                $params,
            );
            $result = $response->json();

            if (
                !$response->successful() ||
                !isset($result["ok"]) ||
                !$result["ok"]
            ) {
                $this->error(
                    "Failed to send message: " .
                        ($result["description"] ?? "Unknown error"),
                );
                Log::error(
                    "Telegram sendMessage error: " . json_encode($result),
                );
            }
        } catch (\Exception $e) {
            $this->error("Exception when sending message: " . $e->getMessage());
            Log::error("Telegram sendMessage exception: " . $e->getMessage());
        }
    }

    /**
     * Получает эмодзи для статуса
     */
    protected function getStatusEmoji($status)
    {
        $emojis = [
            "new" => "🆕",
            "in_progress" => "🔄",
            "resolved" => "✅",
            "closed" => "🔒",
        ];

        return $emojis[$status] ?? "❓";
    }

    /**
     * Получает человекочитаемый статус заявки
     */
    protected function getHumanReadableStatus($status)
    {
        $statuses = [
            "new" => "Новая",
            "in_progress" => "В работе",
            "resolved" => "Решена",
            "closed" => "Закрыта",
        ];

        return $statuses[$status] ?? $status;
    }

    /**
     * Получает эмодзи для приоритета
     */
    protected function getPriorityEmoji($priority)
    {
        $emojis = [
            "low" => "🟢",
            "medium" => "🟡",
            "high" => "🟠",
            "critical" => "🔴",
        ];

        return $emojis[strtolower($priority)] ?? "❓";
    }
}
