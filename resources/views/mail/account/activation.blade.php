@component('mail::message')
# Активация учетной записи

Здравствуйте, {{ $notifiable->name ?? 'Пользователь' }}!

Ваша учетная запись в системе ИКТ была активирована администратором.

## Данные для входа в систему:

**Email:** {{ $notifiable->email }}

**Временный пароль:**
<div style="padding: 10px; background-color: #f3f4f6; border-radius: 5px; margin: 15px 0; text-align: center; font-size: 20px; font-weight: bold; letter-spacing: 1px; font-family: monospace;">
    {{ $password }}
</div>

Рекомендуем изменить пароль после первого входа в систему.

@component('mail::button', ['url' => route('login'), 'color' => 'primary'])
Войти в систему
@endcomponent

Если у вас возникли вопросы, пожалуйста, свяжитесь с администратором.

С уважением,<br>
Техническая поддержка {{ config('app.name') }}

<small style="color: #718096; font-size: 12px;">
Это автоматическое сообщение, пожалуйста, не отвечайте на него.
</small>
@endcomponent
