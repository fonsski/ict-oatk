<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Вход не должен зависеть от того, доступен ли журнал для записи.
 *
 * Обычная ошибка на входе — неверный пароль, она обрабатывается штатно.
 * Но каждый исход входа сопровождается записью в журнал, и если журнал
 * недоступен (частый случай на сервере — съехавшие права на storage/logs
 * после ручного обновления), сам вызов Log бросал исключение уже после
 * проверки пароля. Пользователь вместо «Неверный пароль» получал 500.
 */
class LoginLoggingResilienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    private function user(): User
    {
        return User::factory()->withRole("technician")->create([
            "phone" => "+79001112233",
            "password" => Hash::make("Parol12345"),
            "is_active" => true,
        ]);
    }

    /**
     * Имитируем недоступный журнал: канал логирования, который на любой
     * записи бросает исключение — ровно как file_put_contents при
     * отсутствии прав.
     */
    private function breakLogging(): void
    {
        Log::swap(new class extends \Illuminate\Log\LogManager {
            public function __construct()
            {
            }

            public function __call($method, $parameters)
            {
                throw new \RuntimeException("Failed to open stream: Permission denied");
            }
        });
    }

    public function test_wrong_password_still_shows_a_message_when_logging_fails(): void
    {
        $user = $this->user();
        $this->breakLogging();

        $response = $this->from(route("login"))->post(route("login"), [
            "login" => $user->phone,
            "password" => "sovsem-ne-tot-parol",
        ]);

        // Именно 302 с ошибкой валидации, а не 500.
        $response->assertRedirect(route("login"));
        $response->assertSessionHasErrors("login");
        $this->assertGuest();
    }

    public function test_successful_login_works_when_logging_fails(): void
    {
        $user = $this->user();
        $this->breakLogging();

        $response = $this->post(route("login"), [
            "login" => $user->phone,
            "password" => "Parol12345",
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_unknown_account_still_shows_a_message_when_logging_fails(): void
    {
        $this->breakLogging();

        $response = $this->from(route("login"))->post(route("login"), [
            "login" => "+79009998877",
            "password" => "cokolibo",
        ]);

        $response->assertRedirect(route("login"));
        $response->assertSessionHasErrors("login");
    }
}
