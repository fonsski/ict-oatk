<?php

namespace Tests\Feature;

use App\Mail\TestMessage;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Команда mail:test — единственный способ узнать, дойдут ли письма, не
 * дожидаясь, пока сотрудник не сможет восстановить пароль.
 */
class MailTestCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // В тестах драйвер по умолчанию — array, а команда такие драйверы
        // намеренно отвергает. Ставим рабочую конфигурацию.
        config([
            "mail.default" => "smtp",
            "mail.from.address" => "ict@oatk.org",
            "mail.from.name" => "ICT Help",
        ]);
    }

    public function test_it_sends_a_message(): void
    {
        Mail::fake();

        $this->artisan("mail:test", ["email" => "someone@oatk.org"])
            ->assertSuccessful();

        Mail::assertSent(TestMessage::class);
    }

    /**
     * Настоящие письма идут очередью, поэтому у команды есть режим,
     * повторяющий именно этот путь.
     */
    public function test_queued_mode_puts_the_message_in_the_queue(): void
    {
        Mail::fake();

        $this->artisan("mail:test", [
            "email" => "someone@oatk.org",
            "--queued" => true,
        ])->assertSuccessful();

        Mail::assertQueued(TestMessage::class);
        Mail::assertNotSent(TestMessage::class);
    }

    /**
     * Драйвер log пишет письмо в журнал целиком — вместе с кодом
     * восстановления пароля. Считать такую настройку рабочей нельзя.
     */
    public function test_it_refuses_the_log_driver(): void
    {
        config(["mail.default" => "log"]);
        Mail::fake();

        $this->artisan("mail:test", ["email" => "someone@oatk.org"])
            ->expectsOutputToContain("никуда не отправляет письма")
            ->assertFailed();

        Mail::assertNothingOutgoing();
    }

    public function test_it_refuses_an_empty_sender(): void
    {
        config(["mail.from.address" => null]);
        Mail::fake();

        $this->artisan("mail:test", ["email" => "someone@oatk.org"])
            ->assertFailed();

        Mail::assertNothingOutgoing();
    }

    public function test_it_rejects_a_malformed_address(): void
    {
        Mail::fake();

        $this->artisan("mail:test", ["email" => "не-адрес"])->assertFailed();

        Mail::assertNothingOutgoing();
    }

    /**
     * Команду запускают через sudo, а её вывод нередко пересылают — пароль
     * от почтового ящика в нём появляться не должен.
     */
    public function test_it_does_not_print_the_password(): void
    {
        config(["mail.mailers.smtp.password" => "очень-секретный-пароль"]);
        Mail::fake();

        $this->artisan("mail:test", ["email" => "someone@oatk.org"])
            ->doesntExpectOutputToContain("очень-секретный-пароль")
            ->assertSuccessful();
    }
}
