<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

class SessionTimeout
{
    /**
     * Время неактивности в минутах, после которого пользователь будет автоматически разлогинен.
     * По умолчанию 30 минут, но может быть переопределено в .env файле.
     */
    protected $timeout;

    public function __construct()
    {
        $this->timeout = config('session.lifetime', 30);
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Проверяем только для аутентифицированных пользователей
        if (Auth::check()) {
            // Получаем время последней активности из сессии
            $lastActivity = Session::get('last_activity');

            // Если время последней активности существует
            if ($lastActivity) {
                // Создаем объекты Carbon для сравнения
                $lastActivityTime = Carbon::createFromTimestamp($lastActivity);
                $currentTime = Carbon::now();

                // Если прошло больше времени, чем указано в $timeout
                if ($currentTime->diffInMinutes($lastActivityTime) >= $this->timeout) {
                    // Выполняем выход пользователя
                    Auth::logout();
                    Session::flush();
                    Session::regenerate();

                    // Перенаправляем на страницу таймаута
                    return redirect()->route('login.timeout');
                }
            }

            // Обновляем время последней активности
            Session::put('last_activity', time());
        }

        return $next($request);
    }
}
