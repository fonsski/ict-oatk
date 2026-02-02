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
    
     * Активация учетной записи пользователя администратором
     *
     * @param User $user
     * @return \Illuminate\Http\RedirectResponse

    public function activate(User $user)
    {
        
        if (!auth()->user()->canManageUsers()) {
            return redirect()
                ->back()
                ->with(
                    "error",
                    "У вас нет прав для выполнения этого действия.",
                );
        }

        
        if ($user->is_active) {
            return redirect()
                ->back()
                ->with("info", "Учетная запись уже активирована.");
        }

        
        $user->update([
            "is_active" => true,
        ]);

        
        try {
            $user->notify(new AccountActivationNotification());
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

            
            session()->flash("activation_data", [
                "user_id" => $user->id,
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
                "Учетная запись успешно активирована. Уведомление отправлено пользователю.",
            );
    }

    
     * Деактивация учетной записи пользователя администратором
     *
     * @param User $user
     * @return \Illuminate\Http\RedirectResponse

    public function deactivate(User $user)
    {
        
        if (!auth()->user()->canManageUsers()) {
            return redirect()
                ->back()
                ->with(
                    "error",
                    "У вас нет прав для выполнения этого действия.",
                );
        }

        
        if ($user->id === auth()->id()) {
            return redirect()
                ->back()
                ->with(
                    "error",
                    "Нельзя деактивировать собственную учетную запись.",
                );
        }

        
        if (!$user->is_active) {
            return redirect()
                ->back()
                ->with("info", "Учетная запись уже деактивирована.");
        }

        
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

    
     * Повторная отправка уведомления об активации учетной записи
     *
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\RedirectResponse

    public function resendActivation(Request $request, User $user)
    {
        
        if (!auth()->user()->canManageUsers()) {
            return redirect()
                ->back()
                ->with(
                    "error",
                    "У вас нет прав для выполнения этого действия.",
                );
        }

        
        if (!$user->is_active) {
            return redirect()
                ->back()
                ->with(
                    "error",
                    "Невозможно отправить данные для входа неактивному пользователю. Сначала активируйте учетную запись.",
                );
        }

        try {
            
            $user->notify(new AccountActivationNotification());

            Log::info("Повторная отправка данных для входа", [
                "user_id" => $user->id,
                "email" => $user->email,
                "admin_id" => auth()->id(),
            ]);

            return redirect()
                ->back()
                ->with(
                    "success",
                    "Уведомление об активации аккаунта успешно отправлено пользователю.",
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
                    "Не удалось отправить уведомление. Проверьте настройки SMTP или попробуйте позже.",
                );
        }
    }
}
