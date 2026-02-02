@extends('layouts.app')

@section('title', 'Сессия истекла - Ошибка 419')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-8 mb-8">
        <div class="text-center mb-8">
            <div class="relative mx-auto mb-6">
                <svg class="w-24 h-24 mx-auto text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                    <circle cx="15.5" cy="8.5" r="1.5"></circle>
                    <path d="M9 14h6"></path>
                    <path d="M9 14v3"></path>
                    <path d="M15 14v3"></path>
                </svg>
                <div class="absolute top-0 right-0 -mt-2 -mr-2 bg-yellow-600 text-white rounded-full w-12 h-12 flex items-center justify-center text-xl font-bold">
                    419
                </div>
            </div>
            <h1 class="text-3xl font-bold text-slate-900 mb-2">Сессия истекла</h1>
            <p class="text-slate-600 text-lg max-w-2xl mx-auto">Ваша сессия истекла из-за длительного периода неактивности или была закрыта в другом окне браузера. Это защитный механизм для обеспечения безопасности ваших данных.</p>
        </div>

        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 mx-auto max-w-2xl">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        Для продолжения работы с системой вам необходимо выполнить вход заново. Все несохраненные данные могли быть утеряны.
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
                <p>Время: {{ now()->format('d.m.Y H:i:s') }}</p>
                <p class="mt-1">Причина: CSRF-токен устарел или отсутствует. Это защитный механизм против межсайтовой подделки запросов.</p>
            </div>
        </div>
    </div>
</div>
@endsection
