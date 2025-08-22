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
            "login" => "required|string|max:20",
            "password" => "required|string",
        ]);

        // Используем номер телефона для аутентификации
        $credentials = [
            "phone" => $request->login,
            "password" => $request->password,
        ];

        // Check if user exists and is active
        $user = User::where("phone", $request->login)->first();

        if (!$user || !$user->is_active) {
            Log::warning("Попытка входа в неактивную учетную запись", [
                "login" => $request->login,
                "login_type" => "phone",
                "ip" => $request->ip(),
                "user_agent" => $request->userAgent(),
            ]);

            throw ValidationException::withMessages([
                "login" => ["Учетная запись заблокирована или не существует."],
            ]);
        }

        if (Auth::attempt($credentials)) {
            // Успешная аутентификация
            $request->session()->regenerate();

            // Обновляем время последнего входа
            $user->updateLastLogin();

            // Логирование успешного входа
            Log::info("Успешный вход пользователя", [
                "user_id" => $user->id,
                "phone" => $request->login,
                "ip" => $request->ip(),
            ]);

            return redirect()->intended("/");
        }

        // Логирование неудачной попытки входа
        Log::warning("Неудачная попытка входа", [
            "phone" => $request->login,
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
