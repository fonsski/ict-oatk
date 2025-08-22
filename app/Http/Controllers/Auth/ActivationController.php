<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AccountActivationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ActivationController extends Controller
{
    /**
     * Активация учетной записи пользователя администратором
     *
     * @param User $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function activate(User $user)
    {
        // Проверяем, имеет ли текущий пользователь права на активацию
        if (!auth()->user()->canManageUsers()) {
            return redirect()
                ->back()
                ->with(
                    "error",
                    "У вас нет прав для выполнения этого действия.",
                );
        }

        // Если пользователь уже активен, просто перенаправляем обратно
        if ($user->is_active) {
            return redirect()
                ->back()
                ->with("info", "Учетная запись уже активирована.");
        }

        // Генерируем временный пароль
        $temporaryPassword = Str::random(10);

        // Активируем пользователя и обновляем пароль
        $user->update([
            "is_active" => true,
            "password" => Hash::make($temporaryPassword),
        ]);

        // Отправляем уведомление с данными для входа
        try {
            $user->notify(
                new AccountActivationNotification($temporaryPassword),
            );
            Log::info("Отправлено уведомление об активации учетной записи", [
                "user_id" => $user->id,
                "email" => $user->email,
                "admin_id" => auth()->id(),
            ]);
        } catch (\Exception $e) {
            Log::error("Ошибка при отправке уведомления об активации", [
                "user_id" => $user->id,
                "email" => $user->email,
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);

            // Сохраняем данные для возможного повторного отправления
            session()->flash("activation_data", [
                "user_id" => $user->id,
                "temp_password" => $temporaryPassword,
                "timestamp" => now()->timestamp,
            ]);

            return redirect()
                ->back()
                ->with(
                    "warning",
                    "Учетная запись активирована, но возникла проблема при отправке email. Проверьте настройки SMTP.",
                );
        }

        return redirect()
            ->back()
            ->with(
                "success",
                "Учетная запись успешно активирована. Данные для входа отправлены на email пользователя.",
            );
    }

    /**
     * Деактивация учетной записи пользователя администратором
     *
     * @param User $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deactivate(User $user)
    {
        // Проверяем, имеет ли текущий пользователь права на деактивацию
        if (!auth()->user()->canManageUsers()) {
            return redirect()
                ->back()
                ->with(
                    "error",
                    "У вас нет прав для выполнения этого действия.",
                );
        }

        // Нельзя деактивировать самого себя
        if ($user->id === auth()->id()) {
            return redirect()
                ->back()
                ->with(
                    "error",
                    "Нельзя деактивировать собственную учетную запись.",
                );
        }

        // Если пользователь уже неактивен, просто перенаправляем обратно
        if (!$user->is_active) {
            return redirect()
                ->back()
                ->with("info", "Учетная запись уже деактивирована.");
        }

        // Деактивируем пользователя
        $user->update([
            "is_active" => false,
        ]);

        Log::info("Учетная запись деактивирована", [
            "user_id" => $user->id,
            "email" => $user->email,
            "admin_id" => auth()->id(),
            "deactivated_at" => now(),
        ]);

        return redirect()
            ->back()
            ->with("success", "Учетная запись успешно деактивирована.");
    }

    /**
     * Повторная отправка уведомления об активации учетной записи
     *
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resendActivation(Request $request, User $user)
    {
        // Проверяем, имеет ли текущий пользователь права
        if (!auth()->user()->canManageUsers()) {
            return redirect()
                ->back()
                ->with(
                    "error",
                    "У вас нет прав для выполнения этого действия.",
                );
        }

        // Проверяем, активен ли пользователь
        if (!$user->is_active) {
            return redirect()
                ->back()
                ->with(
                    "error",
                    "Невозможно отправить данные для входа неактивному пользователю. Сначала активируйте учетную запись.",
                );
        }

        // Генерируем новый временный пароль
        $temporaryPassword = Str::random(10);

        // Обновляем пароль пользователя
        $user->update([
            "password" => Hash::make($temporaryPassword),
        ]);

        try {
            // Отправляем уведомление с данными для входа
            $user->notify(
                new AccountActivationNotification($temporaryPassword),
            );

            Log::info("Повторная отправка данных для входа", [
                "user_id" => $user->id,
                "email" => $user->email,
                "admin_id" => auth()->id(),
            ]);

            return redirect()
                ->back()
                ->with(
                    "success",
                    "Новые данные для входа успешно отправлены на email пользователя.",
                );
        } catch (\Exception $e) {
            Log::error("Ошибка при повторной отправке данных для входа", [
                "user_id" => $user->id,
                "email" => $user->email,
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->with(
                    "error",
                    "Не удалось отправить данные для входа. Проверьте настройки SMTP или попробуйте позже.",
                );
        }
    }
}
