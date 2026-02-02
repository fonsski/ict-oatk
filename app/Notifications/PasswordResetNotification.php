<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class PasswordResetNotification extends Notification implements ShouldQueue
{
    use Queueable;

    
     * Код сброса пароля
     *
     * @var string

    public $resetCode;

    
     * Create a new notification instance.
     *
     * @param string $resetCode
     * @return void

    public function __construct($resetCode)
    {
        $this->resetCode = $resetCode;
    }

    
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array

    public function via($notifiable)
    {
        return ["mail"];
    }

    
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage

    public function toMail($notifiable)
    {
        
        Log::info("Отправка письма для сброса пароля", [
            "email" => $notifiable->email,
            "reset_code" => $this->resetCode,
        ]);

        return new MailMessage()
            ->subject("Сброс пароля в системе ИКТ")
            ->greeting("Здравствуйте, " . $notifiable->name . "!")
            ->line(
                "Вы получили это письмо, потому что мы получили запрос на сброс пароля для вашей учетной записи.",
            )
            ->line(
                "Ваш код для сброса пароля: <strong>" .
                    $this->resetCode .
                    "</strong>",
            )
            ->line("Этот код действителен в течение 30 минут.")
            ->line(
                "Если вы не запрашивали сброс пароля, никаких дальнейших действий не требуется.",
            )
            ->salutation(
                "С уважением, техническая поддержка " . config("app.name"),
            )
            ->markdown("mail.password.reset");
    }

    
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array

    public function toArray($notifiable)
    {
        return [
            "reset_code" => $this->resetCode,
        ];
    }
}
