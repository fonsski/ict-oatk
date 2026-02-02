@extends('layouts.app')

@section('title', 'Требуется авторизация - Ошибка 401')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-8 mb-8">
        <div class="text-center mb-8">
            <div class="relative mx-auto mb-6">
                <svg class="w-24 h-24 mx-auto text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    <path d="M8 11V9a4 4 0 0 1 8 0v2"></path>
                    <rect x="6" y="11" width="12" height="8" rx="1"></rect>
                    <circle cx="12" cy="15" r="1"></circle>
                </svg>
                <div class="absolute top-0 right-0 -mt-2 -mr-2 bg-blue-500 text-white rounded-full w-12 h-12 flex items-center justify-center text-xl font-bold">
                    401
                </div>
            </div>
            <h1 class="text-3xl font-bold text-slate-900 mb-2">Требуется авторизация</h1>
            <p class="text-slate-600 text-lg max-w-2xl mx-auto">Для доступа к запрашиваемой странице необходимо войти в систему. Пожалуйста, авторизуйтесь и повторите попытку.</p>
        </div>

        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6 mx-auto max-w-2xl">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-700">
                        Возможно, ваша сессия истекла или вы еще не вошли в систему. Для продолжения работы с системой необходимо авторизоваться.
                    </p>
                </div>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row justify-center items-center gap-4 mt-8">
            <a href="{{ route('login') }}" class="w-full sm:w-auto px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200 text-center">
                <span class="flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    Войти в систему
                </span>
            </a>
            <a href="{{ route('home') }}" class="w-full sm:w-auto px-6 py-3 bg-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 transition-colors duration-200 text-center">
                <span class="flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7m-7-7v14" />
                    </svg>
                    На главную
                </span>
            </a>
        </div>

        <div class="mt-10 border-t border-gray-200 pt-8">
            <div class="text-center text-gray-500 text-sm">
                <p>Если у вас возникли проблемы с входом, воспользуйтесь функцией восстановления пароля или обратитесь в техническую поддержку.</p>
            </div>
        </div>
    </div>
</div>
@endsection
