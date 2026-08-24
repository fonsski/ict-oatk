<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view("auth.login");
    }

    public function login(Request $request)
    {
        $messages = [
            "login.required" => "Пожалуйста, введите номер телефона",
            "password.required" => "Пожалуйста, введите пароль",
        ];

        $request->validate(
            [
                "login" => "required|string",
                "password" => "required|string",
            ],
            $messages,
        );

        // Нормализуем номер телефона так же, как он хранится в БД (только цифры и +).
        $login = $request->login;
        $cleanPhone = clean_phone($login);

        // Ищем пользователя по нормализованному номеру, а также по последним 10 цифрам
        // (на случай, если номер введён без кода страны или с 8 вместо +7).
        $user = User::where("phone", $cleanPhone)
            ->orWhere("phone", "+7" . substr($cleanPhone, -10))
            ->first();

        if (!$user) {
            $this->safeLog("warning", "Попытка входа в несуществующую учетную запись", [
                "login" => $login,
                "ip" => $request->ip(),
            ]);

            throw ValidationException::withMessages([
                "login" => [
                    "Учетная запись с таким номером телефона не найдена.",
                ],
            ]);
        }

        // Проверяем, активна ли учетная запись
        if (!$user->is_active) {
            $this->safeLog("warning", "Попытка входа в заблокированную учетную запись", [
                "login" => $request->login,
                "ip" => $request->ip(),
                "user_agent" => $request->userAgent(),
                "user_id" => $user->id,
            ]);

            throw ValidationException::withMessages([
                "login" => [
                    "Учетная запись заблокирована. Пожалуйста, обратитесь к администратору.",
                ],
            ]);
        }

        // Используем учетные данные для аутентификации

        // Используем телефон из базы данных для аутентификации
        $credentials = [
            "phone" => $user->phone,
            "password" => $request->password,
        ];

        if (Auth::attempt($credentials)) {
            // Успешная аутентификация
            $request->session()->regenerate();

            // Обновляем время последнего входа
            if (method_exists($user, "updateLastLogin")) {
                $user->updateLastLogin();
            }

            // Логирование успешного входа
            $this->safeLog("info", "Успешный вход пользователя", [
                "user_id" => $user->id,
                "phone" => $user->phone,
                "ip" => $request->ip(),
            ]);

            return redirect()->intended("/");
        }

        // Логирование неудачной попытки входа
        $this->safeLog("warning", "Неудачная попытка входа", [
            "phone" => $user->phone,
            "ip" => $request->ip(),
            "user_agent" => $request->userAgent(),
            "error" => "Неверный пароль",
        ]);

        throw ValidationException::withMessages([
            "login" => [
                "Неверный пароль. Пожалуйста, проверьте введенные данные и попробуйте снова.",
            ],
        ]);
    }

    /**
     * Записать в журнал, не роняя вход при сбое записи.
     *
     * Обычная ошибка на входе — неверный пароль. Она обрабатывается штатно
     * и должна возвращать понятное сообщение. Но если журнал недоступен для
     * записи (частый случай — съехавшие права на storage/logs после ручного
     * обновления), сам вызов Log бросает исключение уже после проверки
     * пароля, и пользователь вместо «Неверный пароль» видит 500.
     *
     * Запись в журнал не должна определять исход аутентификации. Если она не
     * удалась — пробуем сказать об этом через системный лог PHP и продолжаем.
     */
    private function safeLog(string $level, string $message, array $context = []): void
    {
        try {
            Log::{$level}($message, $context);
        } catch (\Throwable $e) {
            error_log("ICT Help: не удалось записать в журнал ({$message}): " . $e->getMessage());
        }
    }

    public function logout(Request $request)
    {
        // Получаем ID пользователя до выхода из системы
        $userId = Auth::id();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Логируем выход пользователя
        if ($userId) {
            Log::info("Пользователь вышел из системы", [
                "user_id" => $userId,
                "ip" => $request->ip(),
            ]);
        }

        // Используем route() вместо redirect("/") для правильного формирования URL
        return redirect()
            ->route("home")
            ->with("status", "Вы успешно вышли из системы");
    }

    /**
     * Перенаправление после превышения максимального времени неактивности сессии
     */
    public function timeout()
    {
        return redirect()
            ->route("login")
            ->with("timeout", true)
            ->with("message", "Ваша сессия истекла из-за неактивности.");
    }
}
