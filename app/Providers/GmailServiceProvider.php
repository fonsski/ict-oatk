<?php

namespace App\Providers;

use App\Mail\Transport\GmailTransport;
use Illuminate\Mail\MailManager;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Bridge\Google\Transport\GmailTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

class GmailServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Регистрируем транспорт Gmail в Mail Manager
        $this->app->afterResolving(MailManager::class, function (
            MailManager $manager,
        ) {
            $manager->extend("gmail", function () {
                // Проверяем наличие необходимых токенов и настроек
                if (!cache()->has("google_access_token")) {
                    // Если токена нет, логируем это событие
                    \Illuminate\Support\Facades\Log::warning(
                        "GmailServiceProvider: No access token found in cache",
                    );

                    // Используем стандартный транспорт Google, но это потребует авторизации
                    // Для Gmail API мы перенаправим на страницу авторизации в следующий раз
                    $gmailFactory = new GmailTransportFactory();
                    return $gmailFactory->create(
                        new Dsn(
                            "gmail+smtp",
                            "default",
                            config("services.google.client_id"),
                            config("services.google.client_secret"),
                            null,
                            ["user" => config("mail.mailers.smtp.username")],
                        ),
                    );
                }

                // Проверим, есть ли refresh token
                if (!cache()->has("google_refresh_token")) {
                    \Illuminate\Support\Facades\Log::warning(
                        "GmailServiceProvider: No refresh token found in cache. Токены OAuth могут истечь без возможности автоматического обновления.",
                    );
                }

                // Если токен есть, используем наш кастомный транспорт с OAuth
                // Логируем создание транспорта
                \Illuminate\Support\Facades\Log::info(
                    "GmailServiceProvider: Creating Gmail Transport with OAuth",
                );

                $transport = new GmailTransport([
                    "client_id" => config("services.google.client_id"),
                    "client_secret" => config("services.google.client_secret"),
                    "redirect" => config("services.google.redirect"),
                ]);

                return $transport;
            });
        });
    }
}
