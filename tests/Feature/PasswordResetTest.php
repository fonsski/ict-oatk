<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\PasswordResetNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Восстановление пароля по коду из письма.
 *
 * Главное, что проверяем: код нельзя пропустить. Раньше маршрут смены
 * пароля довольствовался наличием данных в сессии, а их клал уже сам
 * запрос кода — то есть, зная чужой адрес, пароль можно было сменить,
 * ни разу не открыв почту.
 */
class PasswordResetTest extends TestCase
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
            "email" => "tech@college.local",
            "password" => Hash::make("original-password"),
        ]);
    }

    /**
     * Код известен только из письма — достаём его оттуда же, откуда взял бы
     * получатель.
     */
    private function requestCodeFor(User $user): string
    {
        Notification::fake();

        $this->post(route("password.send"), ["email" => $user->email]);

        $code = null;
        Notification::assertSentTo(
            $user,
            PasswordResetNotification::class,
            function ($notification) use (&$code) {
                $code = $notification->resetCode ?? null;
                return true;
            },
        );

        $this->assertNotNull($code, "Код не попал в уведомление");

        return (string) $code;
    }

    public function test_full_reset_flow_changes_the_password(): void
    {
        $user = $this->user();
        $code = $this->requestCodeFor($user);

        $this->post(route("password.code.check"), ["code" => $code])
            ->assertRedirect(route("password.reset"));

        $this->post(route("password.update"), [
            "password" => "brand-new-password",
            "password_confirmation" => "brand-new-password",
        ])->assertRedirect(route("home"));

        $this->assertTrue(
            Hash::check("brand-new-password", $user->refresh()->password),
        );
    }

    /**
     * Та самая дыра: запросить код на чужой адрес и сразу отправить новый
     * пароль, не подтверждая ничего.
     */
    public function test_password_cannot_be_changed_without_entering_the_code(): void
    {
        $user = $this->user();
        $this->requestCodeFor($user);

        $this->post(route("password.update"), [
            "password" => "attacker-password",
            "password_confirmation" => "attacker-password",
        ])->assertRedirect(route("password.request"));

        $this->assertFalse(
            Hash::check("attacker-password", $user->refresh()->password),
        );
        $this->assertTrue(Hash::check("original-password", $user->password));
        $this->assertGuest();
    }

    public function test_wrong_code_does_not_unlock_the_reset(): void
    {
        $user = $this->user();
        $code = $this->requestCodeFor($user);
        $wrong = $code === "111111" ? "222222" : "111111";

        $this->post(route("password.code.check"), ["code" => $wrong])
            ->assertSessionHasErrors("code");

        $this->post(route("password.update"), [
            "password" => "attacker-password",
            "password_confirmation" => "attacker-password",
        ])->assertRedirect(route("password.request"));

        $this->assertTrue(Hash::check("original-password", $user->refresh()->password));
    }

    /**
     * Шестизначный код перебирается, если попытки не считать.
     */
    public function test_code_is_invalidated_after_repeated_wrong_guesses(): void
    {
        $user = $this->user();
        $code = $this->requestCodeFor($user);
        $wrong = $code === "111111" ? "222222" : "111111";

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route("password.code.check"), ["code" => $wrong]);
        }

        // Шестая попытка сбрасывает всю процедуру — даже с верным кодом.
        $this->post(route("password.code.check"), ["code" => $code])
            ->assertRedirect(route("password.request"));

        $this->post(route("password.update"), [
            "password" => "attacker-password",
            "password_confirmation" => "attacker-password",
        ])->assertRedirect(route("password.request"));

        $this->assertTrue(Hash::check("original-password", $user->refresh()->password));
    }

    public function test_code_works_only_once(): void
    {
        $user = $this->user();
        $code = $this->requestCodeFor($user);

        $this->post(route("password.code.check"), ["code" => $code]);
        $this->post(route("password.update"), [
            "password" => "first-new-password",
            "password_confirmation" => "first-new-password",
        ]);

        // Повторная попытка тем же кодом уже ничего не меняет: данные
        // сброса стёрты, а сам пользователь после успешной смены вошёл в
        // систему, и guest-middleware уводит его с формы восстановления.
        $this->post(route("password.update"), [
            "password" => "second-new-password",
            "password_confirmation" => "second-new-password",
        ])->assertRedirect(route("home"));

        $this->assertTrue(
            Hash::check("first-new-password", $user->refresh()->password),
        );
        $this->assertFalse(
            Hash::check("second-new-password", $user->password),
        );
    }

    /**
     * Форма не должна подсказывать, какие адреса заведены в системе.
     */
    public function test_unknown_address_gets_the_same_answer(): void
    {
        $this->user();

        $known = $this->post(route("password.send"), [
            "email" => "tech@college.local",
        ]);
        $unknown = $this->post(route("password.send"), [
            "email" => "nobody@college.local",
        ]);

        $this->assertSame($known->status(), $unknown->status());
        $this->assertSame(
            $known->headers->get("Location"),
            $unknown->headers->get("Location"),
        );
        $unknown->assertSessionHasNoErrors();
    }

    /**
     * Код в журнале — это возможность сменить пароль любому, у кого есть
     * доступ к логам.
     */
    public function test_code_is_not_written_to_the_log(): void
    {
        $user = $this->user();

        $logged = [];
        Log::listen(function ($message) use (&$logged) {
            $logged[] = $message->message . " " . json_encode($message->context);
        });

        $code = $this->requestCodeFor($user);

        foreach ($logged as $line) {
            $this->assertStringNotContainsString($code, $line);
        }
    }
}
