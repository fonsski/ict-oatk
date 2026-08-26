@extends('layouts.app')

@section('title', 'Статистика кабинетов - ICT Help')

@section('content')
@php
    $totalRooms = max($stats['total_rooms'], 1);
@endphp
<div class="container-width section-padding">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 mb-2">Статистика кабинетов</h1>
            <p class="text-slate-600">Аналитика и отчёты по кабинетам системы</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('room.export') }}" class="btn-secondary">
                Экспорт CSV
            </a>
            <a href="{{ route('room.index') }}" class="btn-outline">
                Назад к списку
            </a>
        </div>
    </div>

    <!-- Overview Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="card p-6 text-center">
            <div class="text-3xl font-bold text-blue-600 mb-2">{{ $stats['total_rooms'] }}</div>
            <div class="text-sm text-slate-600">Всего кабинетов</div>
            <div class="mt-2 text-xs text-slate-500">В системе</div>
        </div>
        <div class="card p-6 text-center">
            <div class="text-3xl font-bold text-green-600 mb-2">{{ $stats['active_rooms'] }}</div>
            <div class="text-sm text-slate-600">Активных</div>
            <div class="mt-2 text-xs text-slate-500">{{ round(($stats['active_rooms'] / $totalRooms) * 100, 1) }}% от общего</div>
        </div>
        <div class="card p-6 text-center">
            <div class="text-3xl font-bold text-red-600 mb-2">{{ $stats['inactive_rooms'] }}</div>
            <div class="text-sm text-slate-600">Неактивных</div>
            <div class="mt-2 text-xs text-slate-500">{{ round(($stats['inactive_rooms'] / $totalRooms) * 100, 1) }}% от общего</div>
        </div>
        <div class="card p-6 text-center">
            <div class="text-3xl font-bold text-purple-600 mb-2">{{ $stats['available_rooms'] }}</div>
            <div class="text-sm text-slate-600">Доступных</div>
            <div class="mt-2 text-xs text-slate-500">{{ round(($stats['available_rooms'] / $totalRooms) * 100, 1) }}% от общего</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Rooms by Type -->
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Кабинеты по типу</h3>
            @if($stats['rooms_by_type']->isEmpty())
                <p class="text-sm text-slate-500">Нет данных</p>
            @else
                <div class="space-y-4">
                    @foreach($stats['rooms_by_type'] as $type => $count)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-4 h-4 bg-blue-500 rounded-full"></div>
                                <span class="text-sm font-medium text-slate-700">{{ \App\Models\Room::TYPES[$type] ?? ($type ?: 'Не указан') }}</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="text-sm font-semibold text-slate-900">{{ $count }}</div>
                                <div class="w-32 bg-slate-200 rounded-full h-2">
                                    <div class="bg-blue-500 h-2 rounded-full" style="width: {{ ($count / $totalRooms) * 100 }}%"></div>
                                </div>
                                <div class="text-xs text-slate-500 w-12 text-right">
                                    {{ round(($count / $totalRooms) * 100, 1) }}%
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Rooms by Status -->
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Кабинеты по статусу</h3>
            @if($stats['rooms_by_status']->isEmpty())
                <p class="text-sm text-slate-500">Нет данных</p>
            @else
                <div class="space-y-4">
                    @foreach($stats['rooms_by_status'] as $status => $count)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-4 h-4 bg-green-500 rounded-full"></div>
                                <span class="text-sm font-medium text-slate-700">{{ \App\Models\Room::STATUSES[$status] ?? ($status ?: 'Не указан') }}</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="text-sm font-semibold text-slate-900">{{ $count }}</div>
                                <div class="w-32 bg-slate-200 rounded-full h-2">
                                    <div class="bg-green-500 h-2 rounded-full" style="width: {{ ($count / $totalRooms) * 100 }}%"></div>
                                </div>
                                <div class="text-xs text-slate-500 w-12 text-right">
                                    {{ round(($count / $totalRooms) * 100, 1) }}%
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8">
        <!-- Rooms by Building -->
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Кабинеты по зданию</h3>
            @if($stats['rooms_by_building']->isEmpty())
                <p class="text-sm text-slate-500">Здания не указаны</p>
            @else
                <div class="space-y-4">
                    @foreach($stats['rooms_by_building'] as $building => $count)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-4 h-4 bg-purple-500 rounded-full"></div>
                                <span class="text-sm font-medium text-slate-700">{{ $building }}</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="text-sm font-semibold text-slate-900">{{ $count }}</div>
                                <div class="w-32 bg-slate-200 rounded-full h-2">
                                    <div class="bg-purple-500 h-2 rounded-full" style="width: {{ ($count / $totalRooms) * 100 }}%"></div>
                                </div>
                                <div class="text-xs text-slate-500 w-12 text-right">
                                    {{ round(($count / $totalRooms) * 100, 1) }}%
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Capacity & Equipment -->
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Вместимость и оснащение</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-600">Суммарная вместимость</span>
                    <span class="text-lg font-semibold text-slate-900">{{ $stats['total_capacity'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-600">Средняя вместимость кабинета</span>
                    <span class="text-lg font-semibold text-slate-900">{{ round($stats['total_capacity'] / $totalRooms, 1) }}</span>
                </div>
                <div class="pt-4 border-t border-slate-200">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-600">Кабинеты с оборудованием</span>
                        <div class="flex items-center space-x-2">
                            <span class="text-lg font-semibold text-slate-900">{{ $stats['rooms_with_equipment'] }}</span>
                            <span class="text-xs text-slate-500">
                                {{ round(($stats['rooms_with_equipment'] / $totalRooms) * 100, 1) }}%
                            </span>
                        </div>
                    </div>
                    <div class="mt-2 flex items-center justify-between">
                        <span class="text-sm text-slate-600">Кабинеты без оборудования</span>
                        <span class="text-lg font-semibold text-slate-900">{{ $stats['total_rooms'] - $stats['rooms_with_equipment'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
