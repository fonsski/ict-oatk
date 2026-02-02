@extends('layouts.app')

@section('title', 'Детали оборудования - ICT')

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
                <li aria-current="page">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-slate-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                        <span class="ml-1 text-sm text-slate-500 md:ml-2">{{ $equipment->inventory_number }}</span>
                    </div>
                </li>
            </ol>
        </nav>
    </div>

    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-900 mb-2">Детали оборудования</h1>
        <p class="text-slate-600">
            Инвентарный номер: <span class="font-semibold">{{ $equipment->inventory_number }}</span>
            @if($equipment->name)
            — {{ $equipment->name }}
            @endif
        </p>
    </div>

    <div class="card p-6 mb-8">
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <dt class="text-sm font-medium text-slate-500 mb-1">Название</dt>
                <dd class="text-base text-slate-900">{{ $equipment->name ?: 'Не указано' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500 mb-1">Статус</dt>
                <dd class="text-base text-slate-900">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        {{ $equipment->status->name }}
                    </span>
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500 mb-1">Категория</dt>
                <dd class="text-base text-slate-900">{{ $equipment->category ? $equipment->category->name : 'Не указана' }}</dd>
            </div>
            <!-- Информация о локации удалена, так как она больше не используется -->
            <div>
                <dt class="text-sm font-medium text-slate-500 mb-1">Кабинет</dt>
                <dd class="text-base text-slate-900">
                    {{ $equipment->room ? $equipment->room->number . ' - ' . $equipment->room->name : 'Не указан' }}
                    <a href="{{ route('equipment.location.history', $equipment) }}" class="ml-2 inline-flex items-center text-xs text-blue-600 hover:text-blue-800">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                        История перемещений
                    </a>
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500 mb-1">Гарантия</dt>
                <dd class="text-base text-slate-900">
                    @if($equipment->has_warranty)
                        @if($equipment->warranty_end_date >= now())
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Действует до {{ $equipment->warranty_end_date->format('d.m.Y') }}
                            </span>
                            <div class="mt-1 text-xs text-slate-500">
                                Осталось: {{ now()->diffInDays($equipment->warranty_end_date) }} дн.
                            </div>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                Истекла {{ $equipment->warranty_end_date->format('d.m.Y') }}
                            </span>
                            <div class="mt-1 text-xs text-slate-500">
                                Истекла {{ $equipment->warranty_end_date->diffForHumans() }}
                            </div>
                        @endif
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800">
                            Нет
                        </span>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500 mb-1">Последнее обслуживание</dt>
                <dd class="text-base text-slate-900">
                    {{ $equipment->last_service_date ? $equipment->last_service_date->format('d.m.Y') : 'Нет данных' }}
                    <a href="{{ route('equipment.service.index', $equipment) }}" class="ml-2 inline-flex items-center text-xs text-blue-600 hover:text-blue-800">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                        История обслуживания
                    </a>
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500 mb-1">Комментарий</dt>
                <dd class="text-base text-slate-900 whitespace-pre-line">{{ $equipment->service_comment ?: 'Не указан' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500 mb-1">Известные проблемы</dt>
                <dd class="text-base text-slate-900 whitespace-pre-line">{{ $equipment->known_issues ?: 'Не указаны' }}</dd>
            </div>
        </dl>

    </div>

    <!-- Действия -->
    <div class="flex flex-col sm:flex-row items-center justify-center sm:justify-between gap-4">
        <a href="{{ route('equipment.index') }}" class="btn-outline w-full sm:w-auto">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Вернуться к списку
        </a>

        <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
            @if(auth()->user()->hasRole(['admin', 'master']))
                <a href="{{ route('equipment.edit', $equipment) }}" class="btn-secondary w-full sm:w-auto">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Изменить
                </a>

                <a href="{{ route('equipment.move.form', $equipment) }}" class="btn-secondary w-full sm:w-auto">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Переместить
                </a>

                @if(auth()->user()->hasRole(['admin', 'master']))
                    <form action="{{ route('equipment.destroy', $equipment) }}" method="POST" class="w-full sm:w-auto">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-danger w-full" onclick="return confirm('Вы уверены, что хотите удалить это оборудование?')">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            Удалить
                        </button>
                    </form>
                @endif
            @endif

            <a href="{{ route('equipment.service.create', $equipment) }}" class="btn-primary w-full sm:w-auto">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Обслужить
            </a>
        </div>
    </div>
</div>
@endsection
