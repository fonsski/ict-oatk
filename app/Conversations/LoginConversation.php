<?php

namespace App\Conversations;

use App\Models\User;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\TelegramBotController;

class LoginConversation extends Conversation
{
    protected $phone;
    protected $password;

    /**
     * Начало диалога авторизации
     */
    public function run()
    {
        $this->askPhone();
    }

    /**
     * Запрос номера телефона
     */
    private function askPhone()
    {
        $this->say(
            "Для авторизации в системе необходимо ввести ваши учетные данные.",
        );
        $this->ask("Введите ваш номер телефона:", function (Answer $answer) {
            $this->phone = $answer->getText();

            // Очищаем номер телефона от форматирования
            $cleanPhone = preg_replace("/[^0-9]/", "", $this->phone);

            // Проверяем, существует ли пользователь с таким номером телефона
            $user = User::where(
                "phone",
                "like",
                "%" . $cleanPhone . "%",
            )->first();
            if (!$user) {
                $this->say(
                    "Пользователь с таким номером телефона не найден. Попробуйте еще раз.",
                );
                $this->askPhone();
                return;
            }

            // Проверяем, активен ли пользователь
            if (!$user->is_active) {
                $this->say(
                    "Ваша учетная запись неактивна. Обратитесь к администратору.",
                );
                return;
            }

            $this->askPassword();
        });
    }

    /**
     * Запрос пароля
     */
    private function askPassword()
    {
        $this->ask("Введите ваш пароль:", function (Answer $answer) {
            $this->password = $answer->getText();

            // Проверка авторизации
            if ($this->attemptLogin()) {
                $this->say(
                    "Авторизация успешна! Теперь вы можете использовать бота.",
                );
                $this->showWelcome();
            } else {
                $this->say("Неверный пароль. Попробуйте еще раз.");
                $this->askPassword();
            }
        });
    }

    /**
     * Попытка авторизации пользователя
     */
    private function attemptLogin()
    {
        // Очищаем номер телефона от форматирования
        $cleanPhone = preg_replace("/[^0-9]/", "", $this->phone);

        $credentials = [
            "phone" => $cleanPhone,
            "password" => $this->password,
            "is_active" => true,
        ];

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // Получаем Telegram ID пользователя
            $telegramId = $this->bot->getUser()->getId();

            // Сохраняем Telegram ID в профиле пользователя, если еще не сохранен
            if (empty($user->telegram_id)) {
                $user->update(["telegram_id" => $telegramId]);
            }

            // Сохраняем информацию о пользователе в кеше для быстрого доступа
            $telegramUserData = [
                "user_id" => $user->id,
                "telegram_id" => $telegramId,
                "authenticated_at" => now(),
            ];

            app(TelegramBotController::class)->saveTelegramUserData(
                $telegramId,
                $telegramUserData,
            );

            // Обновляем время последнего входа
            $user->updateLastLogin();

            return true;
        }

        return false;
    }

    /**
     * Показывает приветственное сообщение после успешной авторизации
     */
    private function showWelcome()
    {
        $cleanPhone = preg_replace("/[^0-9]/", "", $this->phone);
        $user = User::where("phone", "like", "%" . $cleanPhone . "%")->first();
        $message = "👋 Здравствуйте, {$user->name}!\n\n";

        $message .=
            "Вы успешно авторизовались в системе управления заявками.\n\n";
        $message .= "Доступные команды:\n";
        $message .= "/tickets - Показать список текущих заявок\n";
        $message .= "/help - Показать полный список команд";

        $this->say($message);
    }
}
