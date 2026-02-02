@component('mail::message')
# Сброс пароля

Здравствуйте, {{ $notifiable->name ?? 'Пользователь' }}!

Вы получили это письмо, потому что мы получили запрос на сброс пароля для вашей учетной записи в системе ИКТ.

## Ваш код для сброса пароля:
<div style="padding: 10px; background-color: #f3f4f6; border-radius: 5px; margin: 15px 0; text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 2px;">
    {{ $resetCode }}
</div>

Этот код действителен в течение 30 минут.

Если вы не запрашивали сброс пароля, никаких дальнейших действий не требуется.

@component('mail::button', ['url' => route('password.code'), 'color' => 'primary'])
Ввести код сброса
@endcomponent

С уважением,<br>
Техническая поддержка {{ config('app.name') }}

<small style="color: #718096; font-size: 12px;">
Это автоматическое сообщение, пожалуйста, не отвечайте на него.
</small>
@endcomponent
