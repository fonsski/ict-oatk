<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TelegramAuthService
{
    protected TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    
     * Начинает процесс авторизации

    public function startAuth(int $chatId): bool
    {
        
        if ($this->isUserInAuthProcess($chatId)) {
            Log::info('User already in auth process', ['chat_id' => $chatId]);
            return false;
        }

        
        Cache::put("telegram_auth_{$chatId}", [
            'step' => 'phone',
            'attempts' => 0,
            'started_at' => now()
        ], now()->addMinutes(15));

        $message = "🔐 <b>Авторизация в системе</b>\n\n";
        $message .= "Для входа в систему введите ваш номер телефона:";

        return $this->telegramService->sendMessage($chatId, $message);
    }

    
     * Обрабатывает ввод номера телефона

    public function processPhone(int $chatId, string $phone): bool
    {
        Log::info('Processing phone number', [
            'chat_id' => $chatId,
            'phone' => $phone
        ]);

        $authState = $this->getAuthState($chatId);
        if (!$authState || $authState['step'] !== 'phone') {
            Log::warning('No auth state or wrong step', [
                'chat_id' => $chatId,
                'auth_state' => $authState
            ]);
            return false;
        }

        
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        Log::info('Cleaned phone number', [
            'chat_id' => $chatId,
            'original_phone' => $phone,
            'clean_phone' => $cleanPhone
        ]);

        
        $user = User::where(function ($query) use ($cleanPhone) {
            $query->where('phone', 'like', "%{$cleanPhone}%")
                  ->orWhere('phone', 'like', "%" . substr($cleanPhone, -10) . "%")
                  ->orWhere('phone', $cleanPhone);
        })->first();

        Log::info('User search result', [
            'chat_id' => $chatId,
            'clean_phone' => $cleanPhone,
            'user_found' => $user ? true : false,
            'user_id' => $user ? $user->id : null,
            'user_name' => $user ? $user->name : null
        ]);

        if (!$user) {
            $this->incrementAuthAttempts($chatId);
            $message = "❌ Пользователь с таким номером телефона не найден.\n\n";
            $message .= "Попробуйте еще раз или отправьте /login для начала заново.";
            return $this->telegramService->sendMessage($chatId, $message);
        }

        if (!$user->is_active) {
            $this->clearAuthState($chatId);
            $message = "❌ Ваша учетная запись неактивна.\n\n";
            $message .= "Обратитесь к администратору для активации.";
            return $this->telegramService->sendMessage($chatId, $message);
        }

        
        Cache::put("telegram_auth_{$chatId}", [
            'step' => 'password',
            'phone' => $cleanPhone,
            'user_id' => $user->id,
            'attempts' => 0,
            'started_at' => $authState['started_at']
        ], now()->addMinutes(15));

        Log::info('Moving to password step', [
            'chat_id' => $chatId,
            'user_id' => $user->id,
            'user_name' => $user->name
        ]);

        $message = "✅ Пользователь найден: <b>{$user->name}</b>\n\n";
        $message .= "Введите ваш пароль:";

        return $this->telegramService->sendMessage($chatId, $message);
    }

    
     * Обрабатывает ввод пароля

    public function processPassword(int $chatId, string $password): bool
    {
        $authState = $this->getAuthState($chatId);
        if (!$authState || $authState['step'] !== 'password') {
            return false;
        }

        $user = User::find($authState['user_id']);
        if (!$user) {
            $this->clearAuthState($chatId);
            $message = "❌ Пользователь не найден. Начните авторизацию заново.";
            return $this->telegramService->sendMessage($chatId, $message);
        }

        
        if (!Hash::check($password, $user->password)) {
            $this->incrementAuthAttempts($chatId);
            $message = "❌ Неверный пароль.\n\n";
            $message .= "Попробуйте еще раз или отправьте /login для начала заново.";
            return $this->telegramService->sendMessage($chatId, $message);
        }

        
        $this->completeAuth($chatId, $user);
        return true;
    }

    
     * Завершает авторизацию

    protected function completeAuth(int $chatId, User $user): void
    {
        
        Auth::login($user);

        
        $user->update(['telegram_id' => $chatId]);

        
        Cache::put("telegram_user_{$chatId}", [
            'user_id' => $user->id,
            'authenticated_at' => now(),
            'last_activity' => now()
        ], now()->addDays(30));

        
        $this->clearAuthState($chatId);

        
        $message = "🎉 <b>Авторизация успешна!</b>\n\n";
        $message .= "👋 Здравствуйте, <b>{$user->name}</b>!\n\n";
        $message .= "Вы успешно авторизовались в системе управления заявками.\n\n";
        $message .= "📋 <b>Доступные команды:</b>\n";
        $message .= "• <code>/tickets</code> - Список заявок\n";
        $message .= "• <code>/active</code> - Активные заявки\n";
        $message .= "• <code>/help</code> - Справка";

        $this->telegramService->sendMessage($chatId, $message);

        
        $user->updateLastLogin();

        Log::info('User authenticated successfully', [
            'chat_id' => $chatId,
            'user_id' => $user->id,
            'user_name' => $user->name
        ]);
    }

    
     * Проверяет авторизацию пользователя

    public function isUserAuthenticated(int $chatId): bool
    {
        $userData = Cache::get("telegram_user_{$chatId}");
        
        if (!$userData || !isset($userData['user_id'])) {
            return false;
        }

        
        $lastActivity = $userData['last_activity'] ?? $userData['authenticated_at'];
        if (now()->diffInDays($lastActivity) > 7) {
            $this->clearUserSession($chatId);
            return false;
        }

        
        $userData['last_activity'] = now();
        Cache::put("telegram_user_{$chatId}", $userData, now()->addDays(30));

        return true;
    }

    
     * Получает данные авторизованного пользователя

    public function getAuthenticatedUser(int $chatId): ?User
    {
        if (!$this->isUserAuthenticated($chatId)) {
            return null;
        }

        $userData = Cache::get("telegram_user_{$chatId}");
        return User::find($userData['user_id']);
    }

    
     * Завершает сессию пользователя

    public function logout(int $chatId): bool
    {
        $this->clearUserSession($chatId);
        $message = "👋 Вы вышли из системы.\n\n";
        $message .= "Для повторной авторизации отправьте <code>/login</code>";
        
        return $this->telegramService->sendMessage($chatId, $message);
    }

    
     * Проверяет, находится ли пользователь в процессе авторизации

    public function isUserInAuthProcess(int $chatId): bool
    {
        return Cache::has("telegram_auth_{$chatId}");
    }

    
     * Получает состояние авторизации

    public function getAuthState(int $chatId): ?array
    {
        return Cache::get("telegram_auth_{$chatId}");
    }

    
     * Очищает состояние авторизации

    protected function clearAuthState(int $chatId): void
    {
        Cache::forget("telegram_auth_{$chatId}");
    }

    
     * Очищает сессию пользователя

    protected function clearUserSession(int $chatId): void
    {
        Cache::forget("telegram_user_{$chatId}");
    }

    
     * Сбрасывает блокировку авторизации (для разработки)

    public function resetAuthBlock(int $chatId): bool
    {
        $this->clearAuthState($chatId);
        $this->clearUserSession($chatId);
        
        $message = "🔄 <b>Блокировка авторизации сброшена</b>\n\n";
        $message .= "Теперь вы можете попробовать авторизоваться снова.\n\n";
        $message .= "Отправьте <code>/login</code> для начала авторизации.";
        
        return $this->telegramService->sendMessage($chatId, $message);
    }

    
     * Увеличивает количество попыток авторизации

    protected function incrementAuthAttempts(int $chatId): void
    {
        $authState = $this->getAuthState($chatId);
        if (!$authState) {
            return;
        }

        $authState['attempts'] = ($authState['attempts'] ?? 0) + 1;

        
        if ($authState['attempts'] >= 5) {
            $this->clearAuthState($chatId);
            $message = "🚫 Слишком много неудачных попыток авторизации.\n\n";
            $message .= "Попробуйте снова через 1 минуту.";
            $this->telegramService->sendMessage($chatId, $message);
            return;
        }

        Cache::put("telegram_auth_{$chatId}", $authState, now()->addMinutes(15));
    }
}
