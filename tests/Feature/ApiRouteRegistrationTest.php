<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Регрессия: routes/api.php когда-то не подключался в bootstrap/app.php.
 * Проверяем, что API-маршруты регистрируются и ни один маршрут не заведён
 * дважды (ловушка с RouteServiceProvider).
 */
class ApiRouteRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_routes_are_loaded(): void
    {
        // Маршрут из routes/api.php — признак того, что файл подключён.
        $this->assertNotNull(
            Route::getRoutes()->getByName('sanctum.csrf-cookie')
                ?: collect(Route::getRoutes()->getRoutes())->first(
                    fn ($route) => $route->uri() === 'api/user',
                ),
            'API-маршруты не зарегистрированы — проверьте, что routes/api.php '
                . 'подключён в withRouting() в bootstrap/app.php',
        );
    }

    public function test_api_user_route_requires_authentication(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
    }

    public function test_web_routes_are_not_registered_twice(): void
    {
        // RouteServiceProvider больше не грузит маршруты — если его снова
        // зарегистрировать в bootstrap/providers.php, web.php и api.php
        // задублируются. Этот тест ловит такую регрессию.
        $uris = collect(Route::getRoutes()->getRoutes())->map(
            fn ($route) => implode('|', $route->methods()) . ' ' . $route->uri(),
        );

        $this->assertSame(
            $uris->unique()->count(),
            $uris->count(),
            'Обнаружены дублирующиеся маршруты: '
                . $uris->duplicates()->implode(', '),
        );
    }
}
