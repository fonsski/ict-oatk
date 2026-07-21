<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

/**
 * Маршруты регистрируются в bootstrap/app.php через withRouting().
 * Здесь остаётся только константа HOME, используемая middleware
 * RedirectIfAuthenticated. Загрузку маршрутов сюда добавлять нельзя —
 * иначе web.php и api.php будут зарегистрированы дважды.
 */
class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/';
}
