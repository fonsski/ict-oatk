<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Проверочное письмо для `php artisan mail:test --queued`.
 *
 * Настоящие письма системы — код восстановления пароля, уведомления по
 * заявкам — уходят через очередь. Проверять почту прямой отправкой мало:
 * рабочий процесс очереди держит свою копию конфигурации и после правки
 * .env продолжает ходить на старый SMTP, пока его не перезапустят. Это
 * письмо идёт тем же путём, что и остальные, и потому ловит такой случай.
 *
 * ShouldQueue здесь намеренно не реализован: с ним Mail::send() сам ставит
 * письмо в очередь вместо отправки и рапортует об успехе, даже когда до
 * SMTP-сервера не достучаться. Очередь включается явно — Mail::queue().
 */
class TestMessage extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly string $sentAt)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "ICT Help — проверка отправки почты");
    }

    public function content(): Content
    {
        return new Content(text: "emails.test");
    }
}
