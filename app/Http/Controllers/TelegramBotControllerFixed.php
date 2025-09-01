<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use App\Services\NotificationService;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Cache\LaravelCache;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Telegram\TelegramDriver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class TelegramBotControllerFixed extends Controller
{
    protected $botman;
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        try {
            DriverManager::loadDriver(TelegramDriver::class);

            $token = config("services.telegram.token");
            Log::info(
                "Initializing TelegramDriver with token: " .
                    (empty($token) ? "EMPTY!" : "TOKEN PRESENT"),
            );

            $config = [
                "telegram" => [
                    "token" => $token,
                ],
            ];

            $this->botman = BotManFactory::create($config, new LaravelCache());
            $this->notificationService = $notificationService;

            $this->setupConversations();

            Log::info("TelegramBotController initialized successfully");
        } catch (\Exception $e) {
            Log::error(
                "Error initializing TelegramBotController: " . $e->getMessage(),
            );
            Log::error("Exception trace: " . $e->getTraceAsString());
        }
    }

    /**
     * Обработчик входящих сообщений от Telegram
     */
    public function handle(Request $request)
    {
        Log::info("Received webhook from Telegram: " . $request->getContent());

        try {
            $this->botman->listen();
            Log::info("BotMan processed the request successfully");
            return response()->json(["status" => "success"]);
        } catch (\Exception $e) {
            Log::error("Error handling Telegram webhook: " . $e->getMessage());
            Log::error("Exception trace: " . $e->getTraceAsString());
            return response()->json(
                ["status" => "error", "message" => $e->getMessage()],
                500,
            );
        }
    }

    /**
     * Настройка команд и диалогов бота
     */
    public function setupConversations($botman = null)
    {
        $bot = $botman ?? $this->botman;

        Log::info("Setting up bot conversations");

        // Команда старт - показывает приветствие и запрашивает авторизацию
        $bot->hears("/start", function (BotMan $bot) {
            Log::info("Received /start command");
            $bot->reply(
                "Добро пожаловать в систему управления заявками!\n\nДля начала работы вам нужно авторизоваться. Отправьте команду /login для входа.",
            );
        });

        // Команда авторизации
        $this->botman->hears("/login", function (BotMan $bot) {
            $this->handleLoginCommand($bot);
        });

        // Вывод справки
        $this->botman->hears("/help", function (BotMan $bot) {
            $this->showHelp($bot);
        });

        // Показать список текущих заявок
        $this->botman->hears("/tickets", function (BotMan $bot) {
            $this->showTickets($bot);
        });

        // Показать подробную информацию о заявке
        $this->botman->hears("/ticket_{id}", function (BotMan $bot, $id) {
            $this->showTicketDetails($bot, $id);
        });

        // Взять заявку в работу
        $this->botman->hears("/start_ticket_{id}", function (BotMan $bot, $id) {
            $this->startTicket($bot, $id);
        });

        // Назначить заявку себе
        $this->botman->hears("/assign_{id}", function (BotMan $bot, $id) {
            $this->assignTicket($bot, $id);
        });

        // Выйти из системы
        $this->botman->hears("/logout", function (BotMan $bot) {
            $userId = $bot->getUser()->getId();
            $this->logoutUser($bot, $userId);
            $bot->reply("Вы успешно вышли из системы.");
        });

        // Обработка текстовых сообщений для авторизации
        $this->botman->receivesText(function (BotMan $bot, $text) {
            $userId = $bot->getUser()->getId();
            $authState = $this->getAuthState($userId);

            if ($authState) {
                $this->continueAuthProcess($bot, $text, $authState, $userId);
                return;
            }

            $bot->reply(
                "Извините, я не понимаю эту команду. Отправьте /help для получения списка доступных команд.",
            );
        });
    }

    /**
     * Проверка авторизации пользователя
     */
    private function isAuthenticated(BotMan $bot)
    {
        $userId = $bot->getUser()->getId();
        $telegramUserData = $this->getTelegramUserData($userId);

        if (!$telegramUserData || !isset($telegramUserData["user_id"])) {
            $bot->reply(
                "Для выполнения этой команды необходимо авторизоваться. Отправьте /login для входа.",
            );
            return false;
        }

        // Проверка существования пользователя
        $user = User::find($telegramUserData["user_id"]);
        if (!$user) {
            $bot->reply(
                "Ваша сессия устарела. Пожалуйста, авторизуйтесь снова с помощью команды /login.",
            );
            $this->logoutUser($bot, $userId);
            return false;
        }

        return $user;
    }

    /**
     * Получение данных пользователя Telegram из хранилища
     */
    private function getTelegramUserData($telegramId)
    {
        return cache()->get("telegram_user_" . $telegramId);
    }

    /**
     * Сохранение данных пользователя Telegram в хранилище
     */
    public function saveTelegramUserData($telegramId, $userData)
    {
        cache()->put(
            "telegram_user_" . $telegramId,
            $userData,
            now()->addDays(30),
        );
    }

    /**
     * Получение состояния авторизации пользователя
     */
    private function getAuthState($telegramId)
    {
        return cache()->get("telegram_auth_" . $telegramId);
    }

    /**
     * Сохранение состояния авторизации пользователя
     */
    private function saveAuthState($telegramId, $state)
    {
        cache()->put(
            "telegram_auth_" . $telegramId,
            $state,
            now()->addMinutes(15),
        );
    }

    /**
     * Удаление состояния авторизации пользователя
     */
    private function forgetAuthState($telegramId)
    {
        cache()->forget("telegram_auth_" . $telegramId);
    }

    /**
     * Удаление данных пользователя при выходе
     */
    private function logoutUser(BotMan $bot, $telegramId)
    {
        cache()->forget("telegram_user_" . $telegramId);
    }

    /**
     * Показать справку по командам
     */
    private function showHelp(BotMan $bot)
    {
        $help = "Доступные команды:\n\n";
        $help .= "/login - Войти в систему\n";
        $help .= "/tickets - Показать список текущих заявок\n";
        $help .= "/ticket_{id} - Показать подробную информацию о заявке\n";
        $help .= "/start_ticket_{id} - Взять заявку в работу\n";
        $help .= "/assign_{id} - Назначить заявку себе\n";
        $help .= "/logout - Выйти из системы\n";
        $help .= "/help - Показать эту справку";

        $bot->reply($help);
    }

    /**
     * Обработчик команды логина
     */
    private function handleLoginCommand(BotMan $bot)
    {
        $userId = $bot->getUser()->getId();
        $this->saveAuthState($userId, ["step" => "phone"]);

        $message =
            "Для авторизации в системе необходимо ввести ваши учетные данные.\n\n";
        $message .= "Введите ваш номер телефона:";

        $bot->reply($message);
    }

    /**
     * Продолжение процесса авторизации
     */
    private function continueAuthProcess(
        BotMan $bot,
        $text,
        $authState,
        $userId,
    ) {
        if ($authState["step"] === "phone") {
            $phone = trim($text);

            // Очищаем номер телефона от форматирования
            $cleanPhone = preg_replace("/[^0-9]/", "", $phone);
            Log::info("Trying to find user with phone: " . $cleanPhone);

            // Ищем пользователя различными способами
            $user = User::where(function ($query) use ($cleanPhone) {
                $query
                    ->where("phone", $cleanPhone)
                    ->orWhere("phone", "like", "%" . $cleanPhone . "%")
                    ->orWhere(
                        "phone",
                        "like",
                        "%" . substr($cleanPhone, -10) . "%",
                    );
            })->first();

            if ($user) {
                Log::info(
                    "User found: " . $user->name . " (ID: " . $user->id . ")",
                );
                Log::info("Phone in DB: " . $user->phone);
            }

            if (!$user) {
                $bot->reply(
                    "Пользователь с таким номером телефона не найден. Попробуйте еще раз.",
                );
                return;
            }

            // Проверяем, активен ли пользователь
            if (!$user->is_active) {
                $bot->reply(
                    "Ваша учетная запись неактивна. Обратитесь к администратору.",
                );
                $this->forgetAuthState($userId);
                return;
            }

            // Переходим к вводу пароля
            $this->saveAuthState($userId, [
                "step" => "password",
                "phone" => $cleanPhone,
                "user_id" => $user->id,
            ]);

            $bot->reply("Введите ваш пароль:");
        } elseif ($authState["step"] === "password") {
            $password = $text;
            $userId = $authState["user_id"];

            // Находим пользователя напрямую
            $user = User::find($userId);

            if (!$user) {
                $bot->reply(
                    "Произошла ошибка. Пожалуйста, начните авторизацию заново с команды /login.",
                );
                $this->forgetAuthState($userId);
                return;
            }

            // Проверяем пароль напрямую
            if (Hash::check($password, $user->password)) {
                Log::info("Authentication successful for user: " . $user->name);

                // Авторизуем пользователя
                Auth::login($user);

                // Сохраняем Telegram ID в профиле пользователя
                $telegramId = $bot->getUser()->getId();
                $user->update(["telegram_id" => $telegramId]);

                // Сохраняем данные в кеше
                $this->saveTelegramUserData($telegramId, [
                    "user_id" => $user->id,
                    "telegram_id" => $telegramId,
                    "authenticated_at" => now(),
                ]);

                // Обновляем время последнего входа
                $user->updateLastLogin();

                // Удаляем состояние авторизации
                $this->forgetAuthState($telegramId);

                // Отправляем приветствие
                $message = "👋 Здравствуйте, {$user->name}!\n\n";
                $message .=
                    "Вы успешно авторизовались в системе управления заявками.\n\n";
                $message .= "Доступные команды:\n";
                $message .= "/tickets - Показать список текущих заявок\n";
                $message .= "/help - Показать полный список команд";

                $bot->reply($message);
            } else {
                Log::info("Invalid password for user ID: " . $user->id);
                $bot->reply("Неверный пароль. Попробуйте еще раз.");
            }
        }
    }

    /**
     * Показать список текущих заявок
     */
    private function showTickets(BotMan $bot)
    {
        $user = $this->isAuthenticated($bot);
        if (!$user) {
            return;
        }

        if (!$user->canManageTickets()) {
            $bot->reply("У вас нет прав для просмотра списка заявок.");
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
            $bot->reply("Активных заявок не найдено.");
            return;
        }

        $reply = "📋 *Список активных заявок:*\n\n";

        foreach ($tickets as $ticket) {
            $status =
                $this->getStatusEmoji($ticket->status) .
                " " .
                $this->getHumanReadableStatus($ticket->status);
            $priority =
                $this->getPriorityEmoji($ticket->priority) .
                " " .
                ucfirst($ticket->priority);

            $reply .= "*ID {$ticket->id}*: {$ticket->title}\n";
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

        $bot->reply($reply, ["parse_mode" => "Markdown"]);
    }

    /**
     * Показать детали заявки
     */
    private function showTicketDetails(BotMan $bot, $id)
    {
        $user = $this->isAuthenticated($bot);
        if (!$user) {
            return;
        }

        // Проверяем, есть ли у пользователя права на просмотр заявок
        if (!$user->canManageTickets()) {
            $bot->reply("У вас нет прав для просмотра заявок.");
            return;
        }

        $ticket = Ticket::find($id);

        if (!$ticket) {
            $bot->reply("Заявка с ID {$id} не найдена.");
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

        $reply = "🔍 *Детали заявки #{$ticket->id}*\n\n";
        $reply .= "*Название:* {$ticket->title}\n";
        $reply .= "*Категория:* {$ticket->category}\n";
        $reply .= "*Статус:* {$status}\n";
        $reply .= "*Приоритет:* {$priority}\n\n";

        $reply .= "*Описание:*\n{$ticket->description}\n\n";

        $reply .= "*Заявитель:* {$ticket->reporter_name}\n";
        if ($ticket->reporter_email) {
            $reply .= "*Email:* {$ticket->reporter_email}\n";
        }
        if ($ticket->reporter_phone) {
            $reply .= "*Телефон:* {$ticket->reporter_phone}\n";
        }

        $reply .= "\n*Местоположение:* ";
        if ($ticket->location) {
            $reply .= $ticket->location->name;
            if ($ticket->room) {
                $reply .= ", {$ticket->room->name}";
            }
        } else {
            $reply .= "Не указано";
        }

        $reply .= "\n\n*Исполнитель:* ";
        if ($ticket->assigned_to_id) {
            $reply .= $ticket->assignedTo->name;
        } else {
            $reply .= "Не назначен";
        }

        $reply .= "\n\n*Создано:* " . $ticket->created_at->format("d.m.Y H:i");

        // Добавляем кнопки действий
        $actions = "\n\n*Действия:*\n";

        if (
            $ticket->status !== "in_progress" &&
            $this->canTakeTicketInWork($user, $ticket)
        ) {
            $actions .= "/start_ticket_{$ticket->id} - Взять в работу\n";
        }

        if (!$ticket->assigned_to_id) {
            $actions .= "/assign_{$ticket->id} - Назначить себе\n";
        }

        if (!empty(trim($actions)) && $actions != "\n\n*Действия:*\n") {
            $reply .= $actions;
        }

        $bot->reply($reply, ["parse_mode" => "Markdown"]);
    }

    /**
     * Взять заявку в работу
     */
    private function startTicket(BotMan $bot, $id)
    {
        $user = $this->isAuthenticated($bot);
        if (!$user) {
            return;
        }

        $ticket = Ticket::find($id);

        if (!$ticket) {
            $bot->reply("Заявка с ID {$id} не найдена.");
            return;
        }

        // Проверяем, можно ли взять заявку в работу
        if (!$this->canTakeTicketInWork($user, $ticket)) {
            $bot->reply(
                "У вас нет прав для взятия этой заявки в работу или заявка уже находится в работе.",
            );
            return;
        }

        // Проверяем, не закрыта ли заявка
        if ($ticket->status === "closed") {
            $bot->reply("Нельзя взять в работу закрытую заявку.");
            return;
        }

        $oldStatus = $ticket->status;
        $oldAssignedId = $ticket->assigned_to_id;

        // Обновляем статус и назначаем текущего пользователя исполнителем
        $ticket->update([
            "status" => "in_progress",
            "assigned_to_id" => $user->id,
        ]);

        // Отправляем уведомление об изменении статуса
        $this->notificationService->notifyTicketStatusChanged(
            $ticket,
            $oldStatus,
            "in_progress",
        );

        // Если пользователь не был назначен до этого, отправляем уведомление о назначении
        if ($oldAssignedId !== $user->id) {
            $this->notificationService->notifyTicketAssigned($ticket, $user);
        }

        // Добавляем комментарий о смене статуса и назначении
        $ticket->comments()->create([
            "user_id" => $user->id,
            "content" => "Заявка взята в работу и назначена на {$user->name}",
            "is_system" => true,
        ]);

        $bot->reply(
            "✅ Заявка #{$ticket->id} успешно взята в работу и назначена на вас!",
        );
    }

    /**
     * Назначить заявку себе
     */
    private function assignTicket(BotMan $bot, $id)
    {
        $user = $this->isAuthenticated($bot);
        if (!$user) {
            return;
        }

        // Проверяем, есть ли у пользователя права на назначение заявок
        if (!$user->canManageTickets()) {
            $bot->reply("У вас нет прав для назначения заявок.");
            return;
        }

        $ticket = Ticket::find($id);

        if (!$ticket) {
            $bot->reply("Заявка с ID {$id} не найдена.");
            return;
        }

        // Проверяем, не закрыта ли заявка
        if ($ticket->status === "closed") {
            $bot->reply("Нельзя назначить исполнителя на закрытую заявку.");
            return;
        }

        // Если заявка уже назначена на текущего пользователя
        if ($ticket->assigned_to_id === $user->id) {
            $bot->reply("Заявка #{$ticket->id} уже назначена на вас.");
            return;
        }

        $oldAssignedId = $ticket->assigned_to_id;
        $ticket->update(["assigned_to_id" => $user->id]);

        // Отправляем уведомление о назначении
        $this->notificationService->notifyTicketAssigned($ticket, $user);

        // Добавляем комментарий о назначении
        $ticket->comments()->create([
            "user_id" => $user->id,
            "content" => "Заявка назначена на {$user->name}",
            "is_system" => true,
        ]);

        $bot->reply("✅ Заявка #{$ticket->id} успешно назначена на вас!");
    }

    /**
     * Проверка возможности взять заявку в работу
     */
    private function canTakeTicketInWork(User $user, Ticket $ticket)
    {
        return $user->canManageTickets() && $ticket->status !== "in_progress";
    }

    /**
     * Получить человекочитаемый статус заявки
     */
    private function getHumanReadableStatus($status)
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
     * Получить эмодзи для статуса
     */
    private function getStatusEmoji($status)
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
     * Получить эмодзи для приоритета
     */
    private function getPriorityEmoji($priority)
    {
        $emojis = [
            "low" => "🟢",
            "medium" => "🟡",
            "high" => "🟠",
            "critical" => "🔴",
        ];

        return $emojis[strtolower($priority)] ?? "❓";
    }

    /**
     * Отправить уведомление о новой заявке
     * Вызывается из NotificationService
     */
    public function sendNewTicketNotification(Ticket $ticket)
    {
        // Получаем список пользователей, которые могут обрабатывать заявки и настроили Telegram
        $users = User::whereHas("role", function ($query) {
            $query->whereIn("slug", ["admin", "master", "technician"]);
        })
            ->whereNotNull("telegram_id")
            ->get();

        if ($users->isEmpty()) {
            return;
        }

        $message = "🆕 *Новая заявка #{$ticket->id}*\n\n";
        $message .= "*Название:* {$ticket->title}\n";
        $message .= "*Категория:* {$ticket->category}\n";
        $message .=
            "*Приоритет:* " .
            $this->getPriorityEmoji($ticket->priority) .
            " " .
            ucfirst($ticket->priority) .
            "\n\n";
        $message .= "*Заявитель:* {$ticket->reporter_name}\n\n";
        $message .= "/ticket_{$ticket->id} - Подробнее";

        foreach ($users as $user) {
            try {
                $this->botman->say(
                    $message,
                    $user->telegram_id,
                    TelegramDriver::class,
                    ["parse_mode" => "Markdown"],
                );
            } catch (\Exception $e) {
                Log::error(
                    "Не удалось отправить уведомление в Telegram для пользователя {$user->id}: " .
                        $e->getMessage(),
                );
            }
        }
    }
}
