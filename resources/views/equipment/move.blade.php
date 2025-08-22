@extends('layouts.app')

@section('title', 'Перемещение оборудования')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-lg mx-auto">
        <!-- Заголовок и навигация -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Перемещение оборудования</h1>
            <p class="text-sm text-gray-600">Инвентарный номер: {{ $equipment->inventory_number }} @if($equipment->name) — {{ $equipment->name }}@endif</p>
        </div>

        <!-- Текущий кабинет -->
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

        <!-- Форма перемещения -->
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Форма перемещения</h2>
            </div>
            <div class="p-6">
                <form action="{{ route('equipment.move', $equipment) }}" method="POST">
                    @csrf

                    <!-- Выбор нового кабинета -->
                    <div class="mb-4">
                        <label for="room_id" class="block text-sm font-medium text-gray-700 mb-1">Новый кабинет</label>
                        <select id="room_id" name="room_id" class="form-select block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="">-- Не указывать кабинет --</option>
                            @foreach($rooms as $room)
                                <option value="{{ $room->id }}" {{ $equipment->room_id == $room->id ? 'selected' : '' }}>
                                    {{ $room->number }} - {{ $room->name }} ({{ $room->full_address }})
                                </option>
                            @endforeach
                        </select>
                        @error('room_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Комментарий -->
                    <div class="mb-6">
                        <label for="comment" class="block text-sm font-medium text-gray-700 mb-1">Комментарий к перемещению</label>
                        <textarea id="comment" name="comment" rows="3" class="form-textarea block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="Укажите причину перемещения или другую важную информацию"></textarea>
                        @error('comment')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Кнопки -->
                    <div class="flex justify-end space-x-3">
                        <a href="{{ route('equipment.location.history', $equipment) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded">Отмена</a>
                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Переместить</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection
