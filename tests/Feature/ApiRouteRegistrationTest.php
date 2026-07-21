<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Регрессия: routes/api.php когда-то не подключался в bootstrap/app.php,
 * из-за чего вебхук Telegram отвечал 404, а весь бот был недоступен.
 */
class ApiRouteRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_telegram_webhook_route_is_registered(): void
    {
        $this->assertNotNull(
            Route::getRoutes()->getByAction(
                'App\Http\Controllers\TelegramController@webhook',
            ),
            'Маршрут вебхука Telegram не зарегистрирован — проверьте, что '
                . 'routes/api.php подключён в withRouting() в bootstrap/app.php',
        );
    }

    public function test_telegram_webhook_accepts_updates(): void
    {
        $response = $this->postJson('/api/telegram/webhook', [
            'update_id' => 1,
            'message' => [
                'message_id' => 10,
                'chat' => ['id' => 12345],
                'text' => '/start',
            ],
        ]);

        $response->assertOk()->assertJson(['status' => 'ok']);
    }

    public function test_telegram_webhook_ignores_payload_without_message(): void
    {
        $this->postJson('/api/telegram/webhook', ['update_id' => 2])
            ->assertOk()
            ->assertJson(['status' => 'ok']);
    }

    public function test_telegram_test_endpoint_responds(): void
    {
        $this->getJson('/api/telegram/test')
            ->assertOk()
            ->assertJson(['status' => 'ok']);
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
