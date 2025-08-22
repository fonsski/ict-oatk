@extends('layouts.app')

@section('title', 'Детали оборудования')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-3xl mx-auto bg-white shadow-md rounded-lg p-6">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Оборудование #{{ $equipment->id }}</h1>

        <dl class="grid grid-cols-1 gap-4">
            <div>
                <dt class="text-sm font-medium text-gray-500">Инв. номер / Название</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $equipment->inventory_number }} @if($equipment->name) — <span class="text-gray-700">{{ $equipment->name }}</span>@endif</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Статус</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $equipment->status->name }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Категория</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $equipment->category ? $equipment->category->name : 'Не указана' }}</dd>
            </div>
            <!-- Информация о локации удалена, так как она больше не используется -->
            <div>
                <dt class="text-sm font-medium text-gray-500">Кабинет</dt>
                <dd class="mt-1 text-sm text-gray-900">
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
                <dt class="text-sm font-medium text-gray-500">Гарантия</dt>
                <dd class="mt-1 text-sm text-gray-900">
                    @if($equipment->has_warranty)
                        @if($equipment->warranty_end_date >= now())
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Действует до {{ $equipment->warranty_end_date->format('d.m.Y') }}
                            </span>
                            <div class="mt-1 text-xs text-gray-500">
                                Осталось: {{ now()->diffInDays($equipment->warranty_end_date) }} дн.
                            </div>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                Истекла {{ $equipment->warranty_end_date->format('d.m.Y') }}
                            </span>
                            <div class="mt-1 text-xs text-gray-500">
                                Истекла {{ $equipment->warranty_end_date->diffForHumans() }}
                            </div>
                        @endif
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            Нет
                        </span>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Последнее обслуживание</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $equipment->last_service_date ? $equipment->last_service_date->format('d.m.Y') : 'Нет данных' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Комментарий</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $equipment->service_comment }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Известные проблемы</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $equipment->known_issues }}</dd>
            </div>
        </dl>

        <div class="mt-6 flex gap-3">
            <a href="{{ route('equipment.edit', $equipment) }}" class="bg-blue-500 text-white px-4 py-2 rounded">Изменить</a>
            <a href="{{ route('equipment.move.form', $equipment) }}" class="bg-green-500 text-white px-4 py-2 rounded">Переместить</a>
            <form action="{{ route('equipment.destroy', $equipment) }}" method="POST">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded" onclick="return confirm('Удалить оборудование?')">Удалить</button>
            </form>
            <a href="{{ route('equipment.index') }}" class="text-gray-600 px-4 py-2">Назад</a>
        </div>
    </div>
</div>
@endsection
