<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class AccountActivationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Конструктор без параметров
    }

    /**
     * Определяем маршрут для уведомления через SMS
     *
     * @param  mixed  $notifiable
     * @return string
     */
    public function routeNotificationForSms($notifiable)
    {
        return $notifiable->phone;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        // В будущем можно добавить "sms" для отправки через SMS
        return ["mail"];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        // Логируем отправку для отладки
        Log::info("Отправка сообщения об активации учетной записи", [
            "phone" => $notifiable->phone,
            "user_id" => $notifiable->id,
        ]);

        return new MailMessage()
            ->subject("Ваша учетная запись в системе ИКТ активирована")
            ->greeting("Здравствуйте, " . $notifiable->name . "!")
            ->line("Ваша учетная запись была активирована администратором.")
            ->line(
                "Теперь вы можете войти в систему, используя свой номер телефона и пароль, указанный при регистрации.",
            )
            ->line("Телефон: <strong>" . $notifiable->phone . "</strong>")
            ->action("Войти в систему", url(route("login")))
            ->line(
                "Если у вас возникли вопросы, пожалуйста, свяжитесь с администратором.",
            )
            ->salutation(
                "С уважением, техническая поддержка " . config("app.name"),
            )
            ->markdown("mail.account.activation");
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            "message" => "Ваша учетная запись была активирована.",
        ];
    }
}
