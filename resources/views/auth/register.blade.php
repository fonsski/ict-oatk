@extends('layouts.app')

@section('title', 'Регистрация - ICT')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 via-white to-purple-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <!-- Header -->
        <div class="text-center">
            <div class="mx-auto h-16 w-16 bg-gradient-to-r from-blue-600 to-purple-600 rounded-full flex items-center justify-center mb-6">
                <svg class="h-8 w-8 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="8.5" cy="7" r="4"></circle>
                    <line x1="20" y1="8" x2="20" y2="14"></line>
                    <line x1="23" y1="11" x2="17" y2="11"></line>
                </svg>
            </div>
            <h2 class="text-3xl font-bold text-gray-900 mb-2">Создать аккаунт</h2>
            <p class="text-gray-600">Присоединяйтесь к нашей системе</p>
        </div>

        <!-- Form -->
        <div class="bg-white rounded-xl shadow-xl border border-gray-100 p-8">
            <form method="POST" action="{{ route('register') }}" class="space-y-6">
                @csrf

                <!-- Name Field -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        ФИО
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                        <input type="text"
                               name="name"
                               id="name"
                               value="{{ old('name') }}"
                               class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 @error('name') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror"
                               placeholder="Введите ваше полное имя"
                               required
                               autocomplete="name"
                               autofocus>
                    </div>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Phone Field -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                        Номер телефона
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                        </div>
                        <input type="text"
                               name="phone"
                               id="phone"
                               value="{{ old('phone') }}"
                               class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-300 @error('phone') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror"
                               placeholder="Введите номер телефона"
                               autocomplete="off"
                               style="opacity: 1 !important; position: static !important;"
                               pattern="[0-9+\-\s\(\)]*"
                               minlength="5"
                               required>
                    </div>
                    @error('phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500">Введите номер телефона (пример: +79953940601) - обязательное поле</p>
                </div>

                <!-- Email Field (скрытое поле для совместимости) -->
                <input type="hidden" name="email" value="user_{{ time() }}@example.com">

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
                               placeholder="Создайте надежный пароль"
                               required
                               autocomplete="new-password">
                    </div>
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password Confirmation Field -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                        Подтверждение пароля
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
                               name="password_confirmation"
                               id="password_confirmation"
                               class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                               placeholder="Повторите пароль"
                               required
                               autocomplete="new-password">
                    </div>
                </div>

                <!-- Terms -->
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input type="checkbox"
                               name="terms"
                               id="terms"
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                               required>
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="terms" class="text-gray-700">
                            Я согласен с
                            <a href="{{ route('terms') }}" class="text-blue-600 hover:text-blue-500">условиями использования</a>
                            и
                            <a href="{{ route('privacy') }}" class="text-blue-600 hover:text-blue-500">политикой конфиденциальности</a>
                        </label>
                    </div>
                </div>

                <!-- Submit Button -->
                <div>
                    <button type="submit"
                            class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 active:translate-y-0">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <svg class="h-5 w-5 text-blue-200 group-hover:text-blue-100 transition-colors duration-200" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="8.5" cy="7" r="4"></circle>
                                <line x1="20" y1="8" x2="20" y2="14"></line>
                                <line x1="23" y1="11" x2="17" y2="11"></line>
                            </svg>
                        </span>
                        Создать аккаунт
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

                <!-- Login Link -->
                <div class="text-center">
                    <p class="text-sm text-gray-600">
                        Уже есть аккаунт?
                        <a href="{{ route('login') }}" class="font-medium text-blue-600 hover:text-blue-500 transition-colors duration-200">
                            Войти
                    </a>
                    </p>
                </div>
            </form>
        </div>

        <!-- Footer -->
        <div class="text-center">
            <p class="text-xs text-gray-500">
                Регистрируясь, вы соглашаетесь с нашими
                <a href="{{ route('terms') }}" class="text-blue-600 hover:text-blue-500">условиями использования</a>
            </p>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const phoneInput = document.getElementById('phone');

    // Защита от многократной отправки формы
    const form = document.querySelector('form');
    const submitButton = form.querySelector('button[type="submit"]');

    form.addEventListener('submit', function(e) {
        // Если кнопка уже отключена, предотвращаем отправку
        if (submitButton.disabled) {
            e.preventDefault();
            return;
        }

        // Проверка телефона при отправке
        const phoneValue = phoneInput.value.trim();
        const cleanPhone = phoneValue.replace(/[^\d+]/g, '');

        if (!cleanPhone || cleanPhone.length < 5) {
            e.preventDefault();

            // Показываем ошибку
            phoneInput.classList.add('border-red-300', 'focus:ring-red-500', 'focus:border-red-500');

            // Проверяем, существует ли уже сообщение об ошибке
            let errorMsg = phoneInput.parentNode.parentNode.querySelector('.text-red-600');
            if (!errorMsg) {
                errorMsg = document.createElement('p');
                errorMsg.className = 'mt-1 text-sm text-red-600';
                phoneInput.parentNode.parentNode.appendChild(errorMsg);
            }

            errorMsg.textContent = 'Пожалуйста, введите действительный номер телефона';

            // Прокручиваем к полю с ошибкой
            phoneInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
            phoneInput.focus();

            return;
        }

        // Отключаем кнопку отправки и меняем ее вид
        submitButton.disabled = true;
        submitButton.classList.add('opacity-75', 'cursor-not-allowed');
        submitButton.innerHTML = '<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Создание аккаунта...';

        // Разблокируем кнопку через 10 секунд на случай ошибки
        setTimeout(function() {
            submitButton.disabled = false;
            submitButton.classList.remove('opacity-75', 'cursor-not-allowed');
            submitButton.innerHTML = 'Создать аккаунт';
        }, 10000);
    });

    // Добавляем обработчик ввода для поля телефона
    phoneInput.addEventListener('input', function() {
        // Убираем стили ошибки при вводе
        this.classList.remove('border-red-300', 'focus:ring-red-500', 'focus:border-red-500');

        // Удаляем сообщение об ошибке если оно есть
        const errorMsg = this.parentNode.parentNode.querySelector('.text-red-600');
        if (errorMsg) {
            errorMsg.remove();
        }
    });
});
</script>
@endpush

@endsection
