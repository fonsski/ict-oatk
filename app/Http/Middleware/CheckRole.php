<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Role;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Проверяем аутентификацию
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Проверяем наличие role_id
        if (!$user->role_id) {
            Log::error('User has no role_id: ' . $user->id);
            abort(403, 'У вас не назначена роль в системе.');
        }

        // Загружаем роль, если она еще не загружена
        if (!$user->relationLoaded('role')) {
            $user->load('role');
        }

        // Проверяем существование роли
        if (!$user->role) {
            Log::error('Role not found for user: ' . $user->id . ', role_id: ' . $user->role_id);
            abort(403, 'Роль пользователя не найдена.');
        }

        // Проверяем совпадение с требуемыми ролями
        if (!in_array($user->role->slug, $roles)) {
            Log::warning('User ' . $user->id . ' with role ' . $user->role->slug . ' tried to access restricted area.');
            abort(403, 'У вас нет прав для доступа к этой странице.');
        }

        return $next($request);
    }
}
