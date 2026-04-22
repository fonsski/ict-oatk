@extends('layouts.app')

@section('title', 'История обслуживания оборудования - ICT')

@section('content')
<div class="container-width section-padding">
    <!-- Breadcrumbs -->
    <div class="mb-5">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('dashboard') }}" class="text-sm text-slate-500 hover:text-slate-700 inline-flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path></svg>
                        Главная
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-slate-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                        <a href="{{ route('equipment.index') }}" class="ml-1 text-sm text-slate-500 hover:text-slate-700 md:ml-2">Оборудование</a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-slate-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                        <a href="{{ route('equipment.show', $equipment) }}" class="ml-1 text-sm text-slate-500 hover:text-slate-700 md:ml-2">{{ $equipment->inventory_number }}</a>
                    </div>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-slate-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                        <span class="ml-1 text-sm text-slate-500 md:ml-2">История обслуживания</span>
                    </div>
                </li>
            </ol>
        </nav>
    </div>

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 mb-2">История обслуживания</h1>
            <p class="text-slate-600">
                {{ $equipment->name ?? 'Оборудование' }}
                <span class="font-semibold">({{ $equipment->inventory_number }})</span>
            </p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('equipment.service.create', $equipment) }}" class="btn-primary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Добавить запись
            </a>
        </div>
    </div>

    <!-- Equipment Info Card -->
    <div class="card p-6 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <h3 class="text-sm font-medium text-slate-500 mb-1">Инвентарный номер</h3>
                <p class="text-lg font-semibold text-slate-900">{{ $equipment->inventory_number }}</p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-slate-500 mb-1">Название</h3>
                <p class="text-lg font-semibold text-slate-900">{{ $equipment->name ?? 'Не указано' }}</p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-slate-500 mb-1">Текущее расположение</h3>
                <p class="text-lg font-semibold text-slate-900">
                    @if($equipment->room)
                        Кабинет {{ $equipment->room->number }} - {{ $equipment->room->name ?? $equipment->room->type_name }}
                    @else
                        Не указано
                    @endif
                </p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-slate-500 mb-1">Категория</h3>
                <p class="text-lg font-semibold text-slate-900">{{ $equipment->category->name ?? 'Не указана' }}</p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-slate-500 mb-1">Статус</h3>
                <p class="text-lg font-semibold text-slate-900">{{ $equipment->status->name ?? 'Не указан' }}</p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-slate-500 mb-1">Последнее обслуживание</h3>
                <p class="text-lg font-semibold text-slate-900">
                    {{ $equipment->last_service_date ? $equipment->last_service_date->format('d.m.Y') : 'Не проводилось' }}
                </p>
            </div>
        </div>
    </div>

    <!-- Service History -->
    <div class="card">
        <div class="p-6 border-b border-slate-200">
            <h2 class="text-xl font-semibold text-slate-900">История обслуживания</h2>
        </div>

        @if($serviceHistory->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Дата</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Тип</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Описание</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Результат</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Исполнитель</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        @foreach($serviceHistory as $record)
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-slate-900">{{ $record->service_date->format('d.m.Y') }}</div>
                                    <div class="text-sm text-slate-500">{{ $record->service_date->format('H:i') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ $record->service_type_name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-slate-900 line-clamp-2">{{ Str::limit($record->description, 100) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $resultClasses = [
                                            'success' => 'bg-green-100 text-green-800',
                                            'partial' => 'bg-yellow-100 text-yellow-800',
                                            'failed' => 'bg-red-100 text-red-800',
                                            'pending' => 'bg-orange-100 text-orange-800',
                                        ];
                                        $resultClass = $resultClasses[$record->service_result] ?? 'bg-slate-100 text-slate-800';
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $resultClass }}">
                                        {{ $record->service_result_name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-slate-900">{{ $record->performedBy->name ?? 'Неизвестно' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        <a href="{{ route('equipment.service.show', [$equipment, $record]) }}" class="text-blue-600 hover:text-blue-900">
                                            Просмотр
                                        </a>
                                        @if(auth()->user()->hasRole(['admin', 'master']))
                                            <a href="{{ route('equipment.service.edit', [$equipment, $record]) }}" class="text-indigo-600 hover:text-indigo-900">
                                                Изменить
                                            </a>
                                            <form action="{{ route('equipment.service.destroy', [$equipment, $record]) }}" method="POST" class="inline" onsubmit="return confirm('Вы уверены, что хотите удалить эту запись?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900 bg-transparent border-0 p-0">
                                                    Удалить
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-6 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 mb-4">
                    <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-slate-900 mb-2">Нет записей об обслуживании</h3>
                <p class="text-slate-500 mb-6">Для этого оборудования ещё не добавлены записи об обслуживании.</p>
                <a href="{{ route('equipment.service.create', $equipment) }}" class="btn-primary">
                    Добавить первую запись
                </a>
            </div>
        @endif
    </div>

    <!-- Actions -->
    <div class="mt-8 flex flex-col md:flex-row items-center justify-center md:justify-between gap-4">
        <a href="{{ route('equipment.show', $equipment) }}" class="btn-outline w-full md:w-auto">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Вернуться к оборудованию
        </a>
        <div class="flex flex-col md:flex-row gap-2">
            <a href="{{ route('equipment.location.history', $equipment) }}" class="btn-secondary w-full md:w-auto">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                История перемещений
            </a>
            @if(auth()->user()->hasRole(['admin', 'master']))
                <a href="{{ route('equipment.edit', $equipment) }}" class="btn-secondary w-full md:w-auto">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Редактировать оборудование
                </a>
            @endif
        </div>
    </div>
</div>
@endsection
