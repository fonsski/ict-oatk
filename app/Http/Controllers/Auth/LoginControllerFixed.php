<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LoginControllerFixed extends Controller
{
    public function showLoginForm()
    {
        return view("auth.login");
    }

    public function login(Request $request)
    {
        $request->validate([
            "login" => "required|string|max:20",
            "password" => "required|string",
        ]);

        // Форматируем номер телефона для поиска (удаляем пробелы, скобки и дефисы)
        $cleanPhone = preg_replace("/[^0-9+]/", "", $request->login);

        // Check if user exists and is active
        $user = User::where(function ($query) use ($cleanPhone, $request) {
            // Поиск по номеру как есть и по очищенному номеру
            $query
                ->where("phone", $request->login)
                ->orWhere("phone", $cleanPhone);
        })->first();

        if (!$user || !$user->is_active) {
            Log::warning("Попытка входа в неактивную учетную запись", [
                "login" => $request->login,
                "clean_phone" => $cleanPhone,
                "login_type" => "phone",
                "ip" => $request->ip(),
                "user_agent" => $request->userAgent(),
            ]);

            throw ValidationException::withMessages([
                "login" => ["Учетная запись заблокирована или не существует."],
            ]);
        }

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
        ]);

        throw ValidationException::withMessages([
            "login" => [
                "Предоставленные учетные данные не соответствуют нашим записям.",
            ],
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
