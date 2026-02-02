<?php

namespace App\Providers;

use App\Mail\Transport\GmailTransport;
use Illuminate\Mail\MailManager;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Bridge\Google\Transport\GmailTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

class GmailServiceProvider extends ServiceProvider
{
    
     * Register services.

    public function register(): void
    {
        
    }

    
     * Bootstrap services.

    public function boot(): void
    {
        
        $this->app->afterResolving(MailManager::class, function (
            MailManager $manager,
        ) {
            $manager->extend("gmail", function () {
                
                if (!cache()->has("google_access_token")) {
                    
                    \Illuminate\Support\Facades\Log::warning(
                        "GmailServiceProvider: No access token found in cache",
                    );

                    
                    
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

                
                if (!cache()->has("google_refresh_token")) {
                    \Illuminate\Support\Facades\Log::warning(
                        "GmailServiceProvider: No refresh token found in cache. Токены OAuth могут истечь без возможности автоматического обновления.",
                    );
                }

                
                
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
