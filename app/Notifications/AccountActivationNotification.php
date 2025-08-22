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
     * Логин и пароль для доступа к системе
     */
    protected $password;

    /**
     * Create a new notification instance.
     *
     * @param string $password Временный пароль для первого входа
     * @return void
     */
    public function __construct($password)
    {
        $this->password = $password;
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
        Log::info("Отправка письма активации учетной записи", [
            "phone" => $notifiable->phone,
            "user_id" => $notifiable->id,
        ]);

        return new MailMessage()
            ->subject("Ваша учетная запись в системе ИКТ активирована")
            ->greeting("Здравствуйте, " . $notifiable->name . "!")
            ->line("Ваша учетная запись была активирована администратором.")
            ->line("Ниже приведены данные для входа в систему:")
            ->line("Телефон: <strong>" . $notifiable->phone . "</strong>")
            ->line("Временный пароль: <strong>" . $this->password . "</strong>")
            ->line("Рекомендуем изменить пароль после первого входа в систему.")
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
            "password" => $this->password,
        ];
    }
}
