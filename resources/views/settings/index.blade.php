@extends('layouts.app')

@section('title', 'Настройки профиля')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-900 mb-2">Настройки профиля</h1>
        <p class="text-slate-600">Управление вашим аккаунтом и настройками безопасности</p>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-start">
            <svg class="w-5 h-5 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="space-y-6">
        <!-- Информация о профиле -->
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h2 class="text-lg font-semibold text-slate-900">Информация о профиле</h2>
            </div>
            <div class="px-6 py-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Имя</label>
                        <div class="text-slate-900">{{ $user->name }}</div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Телефон</label>
                        <div class="text-slate-900">{{ $user->phone }}</div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Роль</label>
                        <div class="text-slate-900">
                            @if($user->role)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $user->role->name }}
                                </span>
                            @else
                                <span class="text-slate-500">Не назначена</span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Статус</label>
                        <div class="text-slate-900">
                            @if($user->is_active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Активен
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                    Неактивен
                                </span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Дата регистрации</label>
                        <div class="text-slate-900">{{ $user->created_at->format('d.m.Y H:i') }}</div>
                    </div>
                    @if($user->last_login_at)
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Последний вход</label>
                        <div class="text-slate-900">{{ $user->last_login_at->format('d.m.Y H:i') }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Безопасность -->
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h2 class="text-lg font-semibold text-slate-900">Безопасность</h2>
            </div>
            <div class="px-6 py-6">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <h3 class="text-base font-medium text-slate-900 mb-1">Пароль</h3>
                        <p class="text-sm text-slate-600">Измените пароль для повышения безопасности вашего аккаунта</p>
                    </div>
                    <a href="{{ route('settings.change-password.form') }}"
                       class="ml-4 inline-flex items-center px-4 py-2 border border-slate-300 text-sm font-medium rounded-lg text-slate-700 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                        Изменить пароль
                    </a>
                </div>
            </div>
        </div>

        <!-- Статистика -->
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h2 class="text-lg font-semibold text-slate-900">Статистика</h2>
            </div>
            <div class="px-6 py-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center p-4 bg-slate-50 rounded-lg">
                        <div class="text-2xl font-bold text-slate-900 mb-1">{{ $user->tickets()->count() }}</div>
                        <div class="text-sm text-slate-600">Всего заявок</div>
                    </div>
                    <div class="text-center p-4 bg-blue-50 rounded-lg">
                        <div class="text-2xl font-bold text-blue-600 mb-1">{{ $user->tickets()->whereIn('status', ['open', 'in_progress'])->count() }}</div>
                        <div class="text-sm text-slate-600">Активных заявок</div>
                    </div>
                    <div class="text-center p-4 bg-green-50 rounded-lg">
                        <div class="text-2xl font-bold text-green-600 mb-1">{{ $user->tickets()->where('status', 'resolved')->count() }}</div>
                        <div class="text-sm text-slate-600">Решенных заявок</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Действия -->
        <div class="flex justify-between items-center pt-4">
            <a href="{{ route('home') }}"
               class="inline-flex items-center text-sm text-slate-600 hover:text-slate-900 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Вернуться на главную
            </a>
        </div>
    </div>
</div>
@endsection
