@extends('layouts.app')

@section('title', 'История перемещений оборудования')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Заголовок и навигация -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">История перемещений оборудования</h1>
                <p class="text-sm text-gray-600">Инвентарный номер: {{ $equipment->inventory_number }} @if($equipment->name) — {{ $equipment->name }}@endif</p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('equipment.move.form', $equipment) }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Переместить</a>
                <a href="{{ route('equipment.show', $equipment) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded">Назад к оборудованию</a>
            </div>
        </div>

        <!-- Информация о текущем кабинете -->
        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Текущее расположение</h2>
            <div class="flex items-center">
                <div class="bg-blue-100 text-blue-800 p-3 rounded-full mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <div>
                    @if($equipment->room)
                        <p class="font-medium text-gray-900">{{ $equipment->room->number }} - {{ $equipment->room->name }}</p>
                        <p class="text-sm text-gray-600">{{ $equipment->room->full_address }}</p>
                    @else
                        <p class="font-medium text-gray-900">Не указан</p>
                        <p class="text-sm text-gray-600">Оборудование не привязано к кабинету</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Начальное расположение -->
        @if($equipment->initial_room_id)
        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Начальное расположение</h2>
            <div class="flex items-center">
                <div class="bg-green-100 text-green-800 p-3 rounded-full mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                </div>
                <div>
                    @if($equipment->initialRoom)
                        <p class="font-medium text-gray-900">{{ $equipment->initialRoom->number }} - {{ $equipment->initialRoom->name }}</p>
                        <p class="text-sm text-gray-600">{{ $equipment->initialRoom->full_address }}</p>
                    @else
                        <p class="font-medium text-gray-900">Начальный кабинет удален</p>
                        <p class="text-sm text-gray-600">Информация о начальном кабинете недоступна</p>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <!-- История перемещений -->
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">История перемещений</h2>
            </div>

            @if($history->count() > 0)
                <div class="divide-y divide-gray-200">
                    @foreach($history as $record)
                        <div class="px-6 py-4">
                            <div class="flex items-start">
                                <div class="flex-shrink-0 mt-1">
                                    @if($record->is_initial_location)
                                        <div class="bg-green-100 text-green-800 p-2 rounded-full">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                            </svg>
                                        </div>
                                    @elseif(empty($record->from_room_id) && !empty($record->to_room_id))
                                        <div class="bg-blue-100 text-blue-800 p-2 rounded-full">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                    @elseif(!empty($record->from_room_id) && empty($record->to_room_id))
                                        <div class="bg-red-100 text-red-800 p-2 rounded-full">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 15l-3-3m0 0l3-3m-3 3h8M3 12a9 9 0 1118 0 9 9 0 01-18 0z" />
                                            </svg>
                                        </div>
                                    @else
                                        <div class="bg-yellow-100 text-yellow-800 p-2 rounded-full">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                <div class="ml-3 flex-1">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-medium text-gray-900">{{ $record->move_type_text }}</p>
                                        <p class="text-sm text-gray-500">{{ $record->move_date->format('d.m.Y H:i') }}</p>
                                    </div>
                                    <div class="mt-1">
                                        @if($record->from_room_id)
                                            <div class="text-sm text-gray-600">
                                                <span class="font-medium">Откуда:</span>
                                                {{ $record->fromRoom ? $record->fromRoom->number . ' - ' . $record->fromRoom->name : 'Кабинет удален' }}
                                            </div>
                                        @endif
                                        @if($record->to_room_id)
                                            <div class="text-sm text-gray-600">
                                                <span class="font-medium">Куда:</span>
                                                {{ $record->toRoom ? $record->toRoom->number . ' - ' . $record->toRoom->name : 'Кабинет удален' }}
                                            </div>
                                        @endif
                                    </div>
                                    @if($record->comment)
                                        <div class="mt-1 text-sm text-gray-600">
                                            <span class="font-medium">Комментарий:</span> {{ $record->comment }}
                                        </div>
                                    @endif
                                    @if($record->moved_by_user_id)
                                        <div class="mt-1 text-xs text-gray-500">
                                            Выполнил: {{ $record->movedByUser ? $record->movedByUser->name : 'Пользователь удален' }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="px-6 py-8 text-center text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mx-auto mb-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <p>История перемещений пуста</p>
                    <a href="{{ route('equipment.move.form', $equipment) }}" class="inline-block mt-3 text-sm text-blue-600 hover:text-blue-800">Переместить оборудование</a>
                </div>
            @endif
        </div>

    </div>
</div>
@endsection
