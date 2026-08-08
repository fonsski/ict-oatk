<?php

namespace App\Console\Commands;

use App\Mail\TestMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

/**
 * Проверка того, что система действительно умеет отправлять письма.
 *
 * Нужна прежде всего для восстановления пароля: пока почта не настроена,
 * форма честно говорит «код отправлен», а письмо не уходит никуда, и понять
 * это можно только по журналу очереди.
 */
class MailTest extends Command
{
    protected $signature = "mail:test
                            {email : Адрес, на который отправить проверочное письмо}
                            {--queued : Отправить через очередь — тем же путём, что идут настоящие письма}";

    protected $description = "Отправить проверочное письмо и показать текущие настройки почты";

    public function handle(): int
    {
        $email = (string) $this->argument("email");

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("«{$email}» не похож на адрес электронной почты.");
            return self::FAILURE;
        }

        $this->showSettings();

        if (!$this->settingsLookSane()) {
            return self::FAILURE;
        }

        $sentAt = Carbon::now()->format("d.m.Y H:i:s");

        try {
            if ($this->option("queued")) {
                Mail::to($email)->queue(new TestMessage($sentAt));
            } else {
                Mail::to($email)->send(new TestMessage($sentAt));
            }
        } catch (\Throwable $e) {
            $this->newLine();
            $this->error("Письмо не отправлено: " . $e->getMessage());
            $this->explain($e);
            return self::FAILURE;
        }

        $this->newLine();

        if ($this->option("queued")) {
            $this->info("Письмо поставлено в очередь для {$email}.");
            $this->line(
                "Дойдёт, только если работает служба ict-help-queue. Если письма нет:",
            );
            $this->line("  systemctl status ict-help-queue");
            $this->line("  php artisan queue:failed");

            return self::SUCCESS;
        }

        $this->info("Письмо отправлено на {$email}.");
        $this->line(
            "Проверьте ящик, в том числе папку со спамом. Затем прогоните тот же",
        );
        $this->line(
            "путь, которым идут настоящие письма: php artisan mail:test {$email} --queued",
        );

        return self::SUCCESS;
    }

    private function showSettings(): void
    {
        $mailer = (string) config("mail.default");
        $config = config("mail.mailers.{$mailer}", []);

        $rows = [["Драйвер", $mailer]];

        if (($config["transport"] ?? null) === "smtp") {
            $rows[] = ["Сервер", ($config["host"] ?? "—") . ":" . ($config["port"] ?? "—")];
            $rows[] = ["Шифрование", $config["scheme"] ?: "STARTTLS по умолчанию"];
            $rows[] = ["Учётная запись", $config["username"] ?: "— (без авторизации)"];
            // Пароль не показываем: команду запускают через sudo, вывод
            // нередко копируют в переписку.
            $rows[] = [
                "Пароль",
                filled($config["password"] ?? null) ? "задан" : "не задан",
            ];
        }

        $rows[] = [
            "Отправитель",
            (config("mail.from.address") ?: "не задан") .
                " (" .
                (config("mail.from.name") ?: "без имени") .
                ")",
        ];

        $this->table(["Параметр", "Значение"], $rows);
    }

    private function settingsLookSane(): bool
    {
        $mailer = (string) config("mail.default");

        if (in_array($mailer, ["log", "array"], true)) {
            $this->error("Драйвер «{$mailer}» никуда не отправляет письма.");

            if ($mailer === "log") {
                $this->line(
                    "Он пишет письмо целиком в storage/logs/laravel.log — вместе с кодом",
                );
                $this->line(
                    "восстановления пароля. На рабочем сервере так оставлять нельзя.",
                );
            }

            $this->line("Настроить: sudo bash deploy/setup-mail.sh");

            return false;
        }

        if (blank(config("mail.from.address"))) {
            $this->error("Не задан MAIL_FROM_ADDRESS — почтовые серверы отклонят такое письмо.");
            return false;
        }

        return true;
    }

    /**
     * Подсказка по самым частым причинам — иначе разбираться приходится по
     * тексту исключения от почтового сервера.
     */
    private function explain(\Throwable $e): void
    {
        $message = mb_strtolower($e->getMessage());

        $hint = match (true) {
            str_contains($message, "authentication")
                || str_contains($message, "535")
                || str_contains($message, "credentials")
                => "Сервер не принял логин или пароль. У Google и Яндекса обычный пароль от ящика не подходит — нужен пароль приложения.",

            str_contains($message, "connection refused")
                || str_contains($message, "connection could not be established")
                || str_contains($message, "timed out")
                => "До SMTP-сервера не достучаться. Проверьте адрес и порт, а также не режет ли исходящие соединения межсетевой экран колледжа.",

            str_contains($message, "certificate")
                || str_contains($message, "ssl")
                || str_contains($message, "tls")
                => "Не сложилось шифрование. Для порта 587 нужен MAIL_SCHEME=smtp (STARTTLS), для 465 — MAIL_SCHEME=smtps.",

            str_contains($message, "sender")
                || str_contains($message, "from")
                || str_contains($message, "553")
                || str_contains($message, "5.7.1")
                => "Сервер не разрешил отправку от этого адреса. MAIL_FROM_ADDRESS обычно должен совпадать с ящиком из MAIL_USERNAME.",

            str_contains($message, "getaddrinfo")
                || str_contains($message, "name or service not known")
                => "Не разрешается имя SMTP-сервера. Проверьте MAIL_HOST и настройки DNS на виртуалке.",

            default => null,
        };

        if ($hint !== null) {
            $this->newLine();
            $this->warn($hint);
        }
    }
}
