@extends('layouts.app')

@section('title', 'Статистика пользователей - ICT')

@section('content')
<div class="container-width section-padding">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 mb-2">Статистика пользователей</h1>
            <p class="text-slate-600">Аналитика и отчеты по учетным записям системы</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('user.export') }}" class="btn-secondary">
                Экспорт CSV
            </a>
            <a href="{{ route('user.index') }}" class="btn-outline">
                Назад к списку
            </a>
        </div>
    </div>

    <!-- Overview Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="card p-6 text-center">
            <div class="text-3xl font-bold text-blue-600 mb-2">{{ $stats['total_users'] }}</div>
            <div class="text-sm text-slate-600">Всего пользователей</div>
            <div class="mt-2 text-xs text-slate-500">В системе</div>
        </div>
        <div class="card p-6 text-center">
            <div class="text-3xl font-bold text-green-600 mb-2">{{ $stats['active_users'] }}</div>
            <div class="text-sm text-slate-600">Активных</div>
            <div class="mt-2 text-xs text-slate-500">{{ round(($stats['active_users'] / $stats['total_users']) * 100, 1) }}% от общего</div>
        </div>
        <div class="card p-6 text-center">
            <div class="text-3xl font-bold text-red-600 mb-2">{{ $stats['inactive_users'] }}</div>
            <div class="text-sm text-slate-600">Неактивных</div>
            <div class="mt-2 text-xs text-slate-500">{{ round(($stats['inactive_users'] / $stats['total_users']) * 100, 1) }}% от общего</div>
        </div>
        <div class="card p-6 text-center">
            <div class="text-3xl font-bold text-purple-600 mb-2">{{ $stats['recent_registrations'] }}</div>
            <div class="text-sm text-slate-600">За 30 дней</div>
            <div class="mt-2 text-xs text-slate-500">Новых регистраций</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Users by Role Chart -->
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Пользователи по ролям</h3>
            <div class="space-y-4">
                @foreach($stats['users_by_role'] as $roleName => $count)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-4 h-4 bg-blue-500 rounded-full"></div>
                            <span class="text-sm font-medium text-slate-700">{{ $roleName ?: 'Без роли' }}</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="text-sm font-semibold text-slate-900">{{ $count }}</div>
                            <div class="w-32 bg-slate-200 rounded-full h-2">
                                <div class="bg-blue-500 h-2 rounded-full" style="width: {{ ($count / $stats['total_users']) * 100 }}%"></div>
                            </div>
                            <div class="text-xs text-slate-500 w-12 text-right">
                                {{ round(($count / $stats['total_users']) * 100, 1) }}%
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Activity Stats -->
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Активность пользователей</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-600">Пользователи с заявками</span>
                    <div class="flex items-center space-x-2">
                        <span class="text-lg font-semibold text-slate-900">{{ $stats['users_with_tickets'] }}</span>
                        <span class="text-xs text-slate-500">
                            {{ round(($stats['users_with_tickets'] / $stats['total_users']) * 100, 1) }}%
                        </span>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-600">Пользователи без заявок</span>
                    <div class="flex items-center space-x-2">
                        <span class="text-lg font-semibold text-slate-900">{{ $stats['total_users'] - $stats['users_with_tickets'] }}</span>
                        <span class="text-xs text-slate-500">
                            {{ round((($stats['total_users'] - $stats['users_with_tickets']) / $stats['total_users']) * 100, 1) }}%
                        </span>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-200">
                    <div class="text-sm text-slate-600 mb-2">Коэффициент активности</div>
                    <div class="flex items-center space-x-3">
                        <div class="text-2xl font-bold text-green-600">
                            {{ round(($stats['active_users'] / $stats['total_users']) * 100, 1) }}%
                        </div>
                        <div class="text-sm text-slate-500">
                            @if(($stats['active_users'] / $stats['total_users']) * 100 >= 80)
                                Отличный уровень активности
                            @elseif(($stats['active_users'] / $stats['total_users']) * 100 >= 60)
                                Хороший уровень активности
                            @elseif(($stats['active_users'] / $stats['total_users']) * 100 >= 40)
                                Средний уровень активности
                            @else
                                Низкий уровень активности
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="card p-6 mt-8">
        <h3 class="text-lg font-semibold text-slate-900 mb-4">Последняя активность</h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Пользователь</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Роль</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Статус</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Последний вход</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Заявок</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @foreach(\App\Models\User::with(['role', 'tickets'])->orderBy('last_login_at', 'desc')->take(10)->get() as $user)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-slate-900">{{ $user->name }}</div>
                                        <div class="text-sm text-slate-500">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $user->role ? $user->role->name : 'Не назначена' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($user->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Активен
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Неактивен
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                                @if($user->last_login_at)
                                    {{ $user->last_login_at->format('d.m.Y H:i') }}
                                @else
                                    <span class="text-slate-400">Никогда</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                                {{ $user->tickets->count() }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recommendations -->
    <div class="card p-6 mt-8 bg-blue-50 border-blue-200">
        <h3 class="text-lg font-semibold text-blue-900 mb-4">Рекомендации по управлению</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h4 class="font-medium text-blue-900 mb-2">Активность пользователей</h4>
                <ul class="text-sm text-blue-800 space-y-1">
                    @if($stats['inactive_users'] > 0)
                        <li>• {{ $stats['inactive_users'] }} неактивных пользователей требуют внимания</li>
                    @endif
                    @if($stats['users_with_tickets'] < $stats['total_users'] * 0.5)
                        <li>• Менее 50% пользователей создают заявки - рассмотрите обучение</li>
                    @endif
                    @if($stats['recent_registrations'] < 5)
                        <li>• Низкая активность регистраций - проверьте процесс онбординга</li>
                    @endif
                </ul>
            </div>
            <div>
                <h4 class="font-medium text-blue-900 mb-2">Оптимизация системы</h4>
                <ul class="text-sm text-blue-800 space-y-1">
                    @if($stats['active_users'] / $stats['total_users'] < 0.7)
                        <li>• Рассмотрите автоматическую деактивацию неактивных аккаунтов</li>
                    @endif
                    @if($stats['users_by_role']->get('admin', 0) > 3)
                        <li>• Много администраторов - проверьте необходимость</li>
                    @endif
                    <li>• Регулярно обновляйте роли и права доступа</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
