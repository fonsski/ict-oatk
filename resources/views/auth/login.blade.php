@extends('layouts.app')

@section('title', 'Вход - ICT')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 via-white to-purple-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <!-- Header -->
        <div class="text-center">
            <div class="mx-auto h-16 w-16 bg-gradient-to-r from-blue-600 to-purple-600 rounded-full flex items-center justify-center mb-6">
                <svg class="h-8 w-8 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                    <polyline points="10 17 15 12 10 7"></polyline>
                    <line x1="15" y1="12" x2="3" y2="12"></line>
                </svg>
            </div>
            <h2 class="text-3xl font-bold text-gray-900 mb-2">Добро пожаловать</h2>
            <p class="text-gray-600">Войдите в свою учетную запись</p>
        </div>

        <!-- Form -->
        <div class="bg-white rounded-xl shadow-xl border border-gray-100 p-8">
            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                @csrf

                <!-- Login Field (Email or Phone) -->
                <div>
                    <label for="login" class="block text-sm font-medium text-gray-700 mb-2">
                        Номер телефона
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                        </div>
                        <input type="tel"
                               name="login"
                               id="login"
                               required
                               pattern="\+7 \(\d{3}\) \d{3}-\d{2}-\d{2}"
                               maxlength="18"
                               class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-300 @error('login') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror"
                               placeholder="+7 (___) ___-__-__"
                               value="{{ old('login') }}">
                    </div>
                    @error('login')
                        <p class="mt-1 text-sm text-red-600 animate-pulse-once">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500">Формат: +7 (999) 999-99-99</p>
                </div>

                <!-- Password Field -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        Пароль
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect>
                                <circle cx="12" cy="16" r="1"></circle>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                        </div>
                        <input type="password"
                               name="password"
                               id="password"
                               class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 @error('password') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror"
                               placeholder="Введите ваш пароль"
                               required
                               autocomplete="current-password">
                    </div>
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input type="checkbox"
                               name="remember"
                               id="remember"
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="remember" class="ml-2 block text-sm text-gray-700">
                            Запомнить меня
                        </label>
                    </div>

                    <a href="{{ route('password.request') }}" class="text-sm text-blue-600 hover:text-blue-500 transition-colors duration-200">
                        Забыли пароль?
                    </a>
                </div>

                <!-- Submit Button -->
                <div>
                    <button type="submit" id="login-button" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 active:translate-y-0">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <svg class="h-5 w-5 text-blue-200 group-hover:text-blue-100 transition-colors duration-200" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                                <polyline points="10 17 15 12 10 7"></polyline>
                                <line x1="15" y1="12" x2="3" y2="12"></line>
                            </svg>
                        </span>
                        Войти
                    </button>
                </div>

                <!-- Divider -->
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-gray-500">или</span>
                    </div>
                </div>

                <!-- Register Link -->
                <div class="text-center">
                    <p class="text-sm text-gray-600">
                        Нет учетной записи?
                        <a href="{{ route('register') }}" class="font-medium text-blue-600 hover:text-blue-500 transition-colors duration-200">
                            Зарегистрироваться
                        </a>
                    </p>
                </div>
            </form>
        </div>

        <!-- Footer -->
        <div class="text-center">
            <p class="text-xs text-gray-500">
                Входя в систему, вы соглашаетесь с нашими
                <a href="{{ route('terms') }}" class="text-blue-600 hover:text-blue-500">условиями использования</a>
            </p>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const loginButton = document.getElementById('login-button');
    const phoneInput = document.querySelector('input[name="login"]');

    // Инициализация маски для телефона
    if (window.initPhoneMasks && phoneInput) {
        window.initPhoneMasks();
    }

    if (form && loginButton) {
        form.addEventListener('submit', function(e) {
            // Если кнопка уже отключена, предотвращаем отправку
            if (loginButton.disabled) {
                e.preventDefault();
                return;
            }

            // Отключаем кнопку отправки и добавляем анимацию
            loginButton.disabled = true;
            loginButton.classList.add('opacity-75', 'cursor-not-allowed');
            loginButton.innerHTML = '<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Вход...';

            // Добавляем скрытое поле с CSRF-токеном заново для предотвращения ошибки 419
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfToken;
            form.appendChild(csrfInput);

            // Разблокируем кнопку через 10 секунд на случай ошибки
            setTimeout(function() {
                loginButton.disabled = false;
                loginButton.classList.remove('opacity-75', 'cursor-not-allowed');
                loginButton.innerHTML = 'Войти';
            }, 10000);
        });
    }

    // Анимация появления формы
    const loginForm = document.querySelector('.space-y-8');
    if (loginForm) {
        loginForm.classList.add('animate-fade-in');
    }
});
</script>
@endpush

@endsection
