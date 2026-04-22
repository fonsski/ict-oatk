@extends('layouts.app')

@section('title', 'Подтверждение кода - ICT')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 via-white to-purple-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <!-- Header -->
        <div class="text-center">
            <div class="mx-auto h-16 w-16 bg-gradient-to-r from-blue-600 to-purple-600 rounded-full flex items-center justify-center mb-6">
                <svg class="h-8 w-8 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 17H2a3 3 0 0 0-3 3 3 3 0 0 0 3 3h20a3 3 0 0 0 3-3 3 3 0 0 0-3-3z"></path>
                    <path d="M22 4H2a3 3 0 0 0-3 3 3 3 0 0 0 3 3h20a3 3 0 0 0 3-3 3 3 0 0 0-3-3z"></path>
                    <path d="M6 11v6"></path>
                    <path d="M18 11v6"></path>
                </svg>
            </div>
            <h2 class="text-3xl font-bold text-gray-900 mb-2">Подтверждение кода</h2>
            <p class="text-gray-600">Введите код, отправленный на ваш телефон</p>
        </div>

        <!-- Status Message -->
        @if (session('status'))
            <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700">
                            {{ session('status') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Form -->
        <div class="bg-white rounded-xl shadow-xl border border-gray-100 p-8">
            <form method="POST" action="{{ route('password.code.check') }}" class="space-y-6">
                @csrf

                <!-- Code Field -->
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-2">
                        Код подтверждения
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                        </div>
                        <input type="text"
                               name="code"
                               id="code"
                               class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 @error('code') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror"
                               placeholder="Введите 6-значный код"
                               required
                               autofocus
                               inputmode="numeric"
                               pattern="[0-9]*"
                               minlength="6"
                               maxlength="6">
                    </div>
                    @error('code')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit Button -->
                <div>
                    <button type="submit"
                            class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <svg class="h-5 w-5 text-blue-200 group-hover:text-blue-100 transition-colors duration-200" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </span>
                        Подтвердить код
                    </button>
                </div>

                <!-- Resend Code -->
                <div class="text-center">
                    <p class="text-sm text-gray-600">
                        Не получили код?
                        <a href="{{ route('password.request') }}" class="font-medium text-blue-600 hover:text-blue-500 transition-colors duration-200">
                            Отправить повторно
                        </a>
                    </p>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
