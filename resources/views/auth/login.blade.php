@extends('layouts.app')

@section('title', 'Вход - ICT Help')

@section('content')
<div class="auth-wrap">
    <div class="auth-box">
        <h1 class="page-title">Вход в систему</h1>
        <p class="auth-lead">Войдите по номеру телефона, чтобы вести заявки и учёт оборудования.</p>

        @if (session('success'))
            <div class="notice notice-success" role="status">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="auth-form">
            @csrf

            <div>
                <label for="login" class="form-label">Номер телефона</label>
                <input type="text"
                       name="login"
                       id="login"
                       required
                       autocomplete="username"
                       class="form-input @error('login') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror"
                       placeholder="+79XXXXXXXXX"
                       value="{{ old('login') }}">
                @error('login')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="form-label">Пароль</label>
                <input type="password"
                       name="password"
                       id="password"
                       class="form-input @error('password') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror"
                       placeholder="Введите пароль"
                       required
                       autocomplete="current-password">
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between gap-4">
                <label for="remember" class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox"
                           name="remember"
                           id="remember"
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    Запомнить меня
                </label>

                <a href="{{ route('password.request') }}" class="text-sm text-blue-600 hover:text-blue-700 hover:underline underline-offset-2">
                    Забыли пароль?
                </a>
            </div>

            <button type="submit" id="login-button" class="btn-primary w-full">Войти</button>
        </form>

        <p class="auth-terms">
            Входя в систему, вы соглашаетесь с
            <a href="{{ route('terms') }}" class="text-blue-600 hover:underline underline-offset-2">условиями использования</a>.
        </p>
    </div>

    @include('auth.partials.aside')
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const loginButton = document.getElementById('login-button');
    const phoneInput = document.querySelector('input[name="login"]');

    if (form && loginButton) {
        form.addEventListener('submit', function(e) {
            // Если кнопка уже отключена, предотвращаем отправку
            if (loginButton.disabled) {
                e.preventDefault();
                return;
            }

            // Никакой предварительной обработки телефона при отправке формы

            // Отключаем кнопку отправки и добавляем анимацию
            loginButton.disabled = true;
            loginButton.classList.add('opacity-75', 'cursor-not-allowed');
            loginButton.innerHTML = '<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Вход...';

            // Разблокируем кнопку через 10 секунд на случай ошибки
            setTimeout(function() {
                loginButton.disabled = false;
                loginButton.classList.remove('opacity-75', 'cursor-not-allowed');
                loginButton.innerHTML = 'Войти';
            }, 10000);
        });
    }

});
</script>
@endpush

@endsection
