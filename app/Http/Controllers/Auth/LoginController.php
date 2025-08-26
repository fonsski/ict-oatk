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
        $request->validate([
            "login" => "required|string",
            "password" => "required|string",
        ]);

        // Форматируем номер телефона для поиска (удаляем пробелы, скобки и дефисы)
        $login = $request->login;
        $cleanPhone = preg_replace("/[^0-9+]/", "", $login);

        // Check if user exists
        $user = User::where("phone", $cleanPhone)
            ->orWhere("phone", $request->login)
            ->orWhere("phone", preg_replace("/^\+/", "", $cleanPhone))
            ->orWhere("phone", "+7" . substr($cleanPhone, -10))
            ->first();

        if (!$user) {
            Log::warning("Попытка входа в несуществующую учетную запись", [
                "login" => $login,
                "ip" => $request->ip(),
            ]);

            throw ValidationException::withMessages([
                "login" => ["Учетная запись не существует."],
            ]);
        }

        // Проверяем, активен ли пользователь, если поле is_active существует
        if (
            array_key_exists("is_active", $user->getAttributes()) &&
            !$user->is_active
        ) {
            // Для администратора (телефон +79953940601) автоматически активируем учетную запись
            if (
                $user->phone === "+79953940601" ||
                $cleanPhone === "+79953940601"
            ) {
                $user->is_active = true;
                $user->save();
            } else {
                Log::warning("Попытка входа в заблокированную учетную запись", [
                    "login" => $request->login,
                    "ip" => $request->ip(),
                    "user_agent" => $request->userAgent(),
                    "user_id" => $user->id,
                ]);

                throw ValidationException::withMessages([
                    "login" => ["Учетная запись заблокирована."],
                ]);
            }
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
            Log::info("Успешный вход пользователя", [
                "user_id" => $user->id,
                "phone" => $user->phone,
                "ip" => $request->ip(),
            ]);

            return redirect()->intended("/");
        }

        // Логирование неудачной попытки входа
        Log::warning("Неудачная попытка входа", [
            "phone" => $user->phone,
            "ip" => $request->ip(),
            "user_agent" => $request->userAgent(),
            "error" => "Неверный пароль",
        ]);

        throw ValidationException::withMessages([
            "login" => ["Неверный пароль."],
        ]);
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

        return redirect("/");
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
