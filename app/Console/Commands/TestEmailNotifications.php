<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\AccountActivationNotification;
use App\Notifications\PasswordResetNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestEmailNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email-notifications
                            {type=all : Тип уведомления (password, activation, all)}
                            {--user=1 : ID пользователя для отправки}
                            {--email= : Email для отправки (если не указан пользователь)}
                            {--smtp : Показать информацию о настройках SMTP}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Отправка тестовых email-уведомлений";

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument("type");
        $userId = $this->option("user");
        $email = $this->option("email");

        if ($email) {
            // Создаем временного пользователя для отправки
            $user = new User([
                "name" => "Тестовый пользователь",
                "email" => $email,
            ]);
            $this->info("Отправка тестовых уведомлений на email: {$email}");
        } else {
            // Находим пользователя по ID
            $user = User::find($userId);
            if (!$user) {
                $this->error("Пользователь с ID {$userId} не найден");
                return 1;
            }
            $this->info(
                "Отправка тестовых уведомлений пользователю: {$user->name} ({$user->email})",
            );

            // Проверяем наличие активного email у пользователя
            if (empty($user->email)) {
                $this->error(
                    "У пользователя отсутствует email адрес. Тестирование невозможно.",
                );
                return 1;
            }
        }

        // Если запрошена информация о SMTP
        if ($this->option("smtp")) {
            $this->showSmtpInfo();
        }

        $success = true;

        if ($type === "password" || $type === "all") {
            $this->info("Отправка уведомления о сбросе пароля...");
            try {
                $resetCode = mt_rand(100000, 999999);
                $user->notify(new PasswordResetNotification($resetCode));
                $this->info(
                    "✓ Уведомление о сбросе пароля отправлено успешно. Код: {$resetCode}",
                );
            } catch (\Exception $e) {
                $this->error(
                    "✗ Ошибка при отправке уведомления о сбросе пароля: " .
                        $e->getMessage(),
                );
                Log::error(
                    "Ошибка при отправке тестового уведомления о сбросе пароля",
                    [
                        "error" => $e->getMessage(),
                        "user" => $user->email ?? "not set",
                    ],
                );
                $success = false;
            }
        }

        if ($type === "activation" || $type === "all") {
            $this->info("Отправка уведомления об активации учетной записи...");
            try {
                $temporaryPassword = "test_password_123";
                $user->notify(
                    new AccountActivationNotification($temporaryPassword),
                );
                $this->info(
                    "✓ Уведомление об активации учетной записи отправлено успешно. Пароль: {$temporaryPassword}",
                );
            } catch (\Exception $e) {
                $this->error(
                    "✗ Ошибка при отправке уведомления об активации: " .
                        $e->getMessage(),
                );
                Log::error(
                    "Ошибка при отправке тестового уведомления об активации",
                    [
                        "error" => $e->getMessage(),
                        "user" => $user->email ?? "not set",
                    ],
                );
                $success = false;
            }
        }

        if ($success) {
            $this->info("Все уведомления отправлены успешно.");

            // Инструкции для проверки
            if (app()->environment("local")) {
                $this->line("\nПроверьте полученные письма:");
                $this->line("- Для Mailhog: http://localhost:8025");
            }

            return 0;
        } else {
            $this->error(
                "Произошли ошибки при отправке уведомлений. Проверьте логи для получения подробной информации.",
            );
            return 1;
        }
    }

    /**
     * Отображение информации о настройках SMTP
     */
    protected function showSmtpInfo()
    {
        $this->info("=== Текущие настройки SMTP ===");
        $this->line("MAILER: " . config("mail.default"));
        $this->line("HOST: " . config("mail.mailers.smtp.host"));
        $this->line("PORT: " . config("mail.mailers.smtp.port"));
        $this->line("USERNAME: " . config("mail.mailers.smtp.username"));
        $this->line(
            "PASSWORD: " .
                str_repeat(
                    "*",
                    strlen(config("mail.mailers.smtp.password") ?: ""),
                ),
        );
        $this->line("ENCRYPTION: " . config("mail.mailers.smtp.encryption"));
        $this->line("FROM ADDRESS: " . config("mail.from.address"));
        $this->line("FROM NAME: " . config("mail.from.name"));

        // Проверка настроек
        $warnings = [];

        if (empty(config("mail.mailers.smtp.host"))) {
            $warnings[] = "SMTP хост не указан";
        }

        if (empty(config("mail.mailers.smtp.username"))) {
            $warnings[] = "SMTP пользователь не указан";
        }

        if (empty(config("mail.mailers.smtp.password"))) {
            $warnings[] = "SMTP пароль не указан";
        }

        if (empty(config("mail.from.address"))) {
            $warnings[] = "Email отправителя не указан";
        }

        if (count($warnings) > 0) {
            $this->warn("\nПредупреждения:");
            foreach ($warnings as $warning) {
                $this->warn("- " . $warning);
            }
            $this->line("\nРекомендуемая конфигурация для Gmail:");
            $this->line("MAIL_MAILER=smtp");
            $this->line("MAIL_HOST=smtp.gmail.com");
            $this->line("MAIL_PORT=587");
            $this->line("MAIL_USERNAME=support.ict@oatk.org");
            $this->line("MAIL_PASSWORD=******");
            $this->line("MAIL_ENCRYPTION=tls");
            $this->line("MAIL_FROM_ADDRESS=support.ict@oatk.org");
            $this->line("MAIL_FROM_NAME=\"ICT Support\"");
        } else {
            $this->info("\nВсе необходимые настройки указаны.");
        }

        $this->line("\n=== Тестирование соединения с SMTP сервером ===");
        try {
            // Проверка соединения с SMTP сервером
            $transport = new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport(
                config("mail.mailers.smtp.host"),
                config("mail.mailers.smtp.port"),
                config("mail.mailers.smtp.encryption") === "tls",
            );
            $transport->setUsername(config("mail.mailers.smtp.username"));
            $transport->setPassword(config("mail.mailers.smtp.password"));

            // Попытка установки соединения
            $transport->start();
            $this->info("Соединение с SMTP сервером успешно установлено!");
            $transport->stop();
        } catch (\Exception $e) {
            $this->error(
                "Ошибка соединения с SMTP сервером: " . $e->getMessage(),
            );
            $this->line("\nПроверьте настройки SMTP в файле .env");
        }

        $this->line("\n");
    }
}
