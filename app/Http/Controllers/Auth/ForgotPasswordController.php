<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\PasswordResetNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ForgotPasswordController extends Controller
{
    /**
     * Показать форму для запроса сброса пароля
     */
    public function showLinkRequestForm()
    {
        return view("auth.passwords.request");
    }

    /**
     * Отправить ссылку для сброса пароля
     */
    public function sendResetCode(Request $request)
    {
        $request->validate([
            "email" => "required|string|email",
        ]);

        $user = User::where("email", $request->email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                "email" => [
                    "Не найден пользователь с указанным email адресом.",
                ],
            ]);
        }

        // Генерируем код сброса пароля
        $resetCode = mt_rand(100000, 999999); // 6-значный код

        // Сохраняем код и время создания в сессии
        $request->session()->put("password_reset_code", [
            "code" => $resetCode,
            "email" => $request->email,
            "created_at" => now(),
            "user_id" => $user->id,
        ]);

        try {
            // Отправляем уведомление с кодом сброса пароля
            $user->notify(new PasswordResetNotification($resetCode));

            // Логируем успешную отправку
            Log::info(
                "Код сброса пароля отправлен для пользователя {$user->id}: {$resetCode}",
                [
                    "email" => $user->email,
                ],
            );
        } catch (\Exception $e) {
            // Логируем ошибку отправки
            Log::error(
                "Ошибка отправки кода сброса пароля для пользователя {$user->id}",
                [
                    "email" => $user->email,
                    "error" => $e->getMessage(),
                    "trace" => $e->getTraceAsString(),
                ],
            );
        }

        // Показываем пользователю страницу для ввода кода
        return redirect()
            ->route("password.code")
            ->with("status", "Код подтверждения отправлен на ваш email.");
    }

    /**
     * Показать форму для ввода кода сброса пароля
     */
    public function showResetCodeForm()
    {
        if (!session()->has("password_reset_code")) {
            return redirect()->route("password.request");
        }

        return view("auth.passwords.code");
    }

    /**
     * Проверить код сброса пароля
     */
    public function validateResetCode(Request $request)
    {
        $request->validate([
            "code" => "required|numeric|digits:6",
        ]);

        $resetData = session("password_reset_code");

        if (!$resetData) {
            return redirect()
                ->route("password.request")
                ->withErrors([
                    "code" =>
                        "Срок действия кода истек. Пожалуйста, запросите новый код.",
                ]);
        }

        // Проверяем, не истек ли срок действия кода (30 минут)
        $expiry = now()->subMinutes(30);
        if ($expiry->gt($resetData["created_at"])) {
            session()->forget("password_reset_code");
            return redirect()
                ->route("password.request")
                ->withErrors([
                    "code" =>
                        "Срок действия кода истек. Пожалуйста, запросите новый код.",
                ]);
        }

        // Проверяем код
        if ($request->code != $resetData["code"]) {
            return back()->withErrors([
                "code" => "Неверный код подтверждения.",
            ]);
        }

        // Перенаправляем на форму создания нового пароля
        return redirect()->route("password.reset");
    }

    /**
     * Показать форму для создания нового пароля
     */
    public function showResetForm()
    {
        if (!session()->has("password_reset_code")) {
            return redirect()->route("password.request");
        }

        return view("auth.passwords.reset");
    }

    /**
     * Сбросить пароль
     */
    public function reset(Request $request)
    {
        $request->validate([
            "password" => "required|string|min:8|confirmed",
        ]);

        $resetData = session("password_reset_code");

        if (!$resetData) {
            return redirect()
                ->route("password.request")
                ->withErrors([
                    "general" =>
                        "Срок действия кода истек. Пожалуйста, запросите новый код.",
                ]);
        }

        // Находим пользователя
        $user = User::find($resetData["user_id"]);

        if (!$user) {
            session()->forget("password_reset_code");
            return redirect()
                ->route("password.request")
                ->withErrors(["general" => "Пользователь не найден."]);
        }

        // Проверяем совпадение email
        if ($user->email !== $resetData["email"]) {
            session()->forget("password_reset_code");
            Log::warning("Попытка сброса пароля с несовпадающим email", [
                "user_id" => $resetData["user_id"],
                "session_email" => $resetData["email"],
                "user_email" => $user->email,
            ]);
            return redirect()
                ->route("password.request")
                ->withErrors([
                    "general" => "Данные для сброса пароля недействительны.",
                ]);
        }

        // Обновляем пароль
        $user->password = Hash::make($request->password);
        $user->save();

        // Логируем успешный сброс пароля
        Log::info("Пароль успешно сброшен для пользователя", [
            "user_id" => $user->id,
            "email" => $user->email,
        ]);

        // Очищаем данные сброса пароля
        session()->forget("password_reset_code");

        // Автоматически входим пользователя
        auth()->login($user);

        return redirect()
            ->route("home")
            ->with("status", "Ваш пароль был успешно изменен.");
    }
}
