@extends('layouts.app')

@section('title', 'Редактировать оборудование')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white shadow-md rounded-lg p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Редактировать оборудование</h1>

            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('equipment.update', $equipment) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label for="inventory_number" class="block text-gray-700 text-sm font-bold mb-2">
                        Инвентарный номер *
                    </label>
                    <input type="text" name="inventory_number" id="inventory_number"
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                           value="{{ old('inventory_number', $equipment->inventory_number) }}" required>
                </div>

                <div class="mb-4">
                    <label for="category_id" class="block text-gray-700 text-sm font-bold mb-2">
                        Категория
                    </label>
                    <select name="category_id" id="category_id"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="">Выберите категорию</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id', $equipment->category_id) == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label for="name" class="block text-gray-700 text-sm font-bold mb-2">
                        Название
                    </label>
                    <input type="text" name="name" id="name"
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                           value="{{ old('name', $equipment->name) }}">
                </div>

                <div class="mb-4">
                    <label for="status_id" class="block text-gray-700 text-sm font-bold mb-2">
                        Статус *
                    </label>
                    <select name="status_id" id="status_id"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            required>
                        <option value="">Выберите статус</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status->id }}"
                                {{ (old('status_id', $equipment->status_id) == $status->id) ? 'selected' : '' }}>
                                {{ $status->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Поле location_id было удалено, так как оно не используется в системе -->

                <div class="mb-4">
                    <label for="room_id" class="block text-gray-700 text-sm font-bold mb-2">
                        Текущий кабинет
                    </label>
                    <select name="room_id" id="room_id"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="">Выберите кабинет</option>
                        @foreach($rooms as $room)
                            <option value="{{ $room->id }}"
                                {{ (old('room_id', $equipment->room_id) == $room->id) ? 'selected' : '' }}>
                                {{ $room->number }} - {{ $room->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label for="initial_room_id" class="block text-gray-700 text-sm font-bold mb-2">
                        Начальный кабинет
                    </label>
                    <select name="initial_room_id" id="initial_room_id"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="">Выберите начальный кабинет</option>
                        @foreach($rooms as $room)
                            <option value="{{ $room->id }}"
                                {{ (old('initial_room_id', $equipment->initial_room_id) == $room->id) ? 'selected' : '' }}>
                                {{ $room->number }} - {{ $room->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-sm text-gray-500 mt-1">Указывает кабинет, в котором оборудование находилось изначально</p>
                </div>

                <div class="mb-4">
                    <label for="has_warranty" class="flex items-center text-gray-700 text-sm font-bold mb-2">
                        <input type="checkbox" name="has_warranty" id="has_warranty" value="1"
                               class="mr-2" {{ old('has_warranty', $equipment->has_warranty) ? 'checked' : '' }}
                               onchange="toggleWarrantyDateField()">
                        Гарантия
                    </label>
                </div>

                <div id="warranty_date_container" class="mb-4 {{ old('has_warranty', $equipment->has_warranty) ? '' : 'hidden' }}">
                    <label for="warranty_end_date" class="block text-gray-700 text-sm font-bold mb-2">
                        Дата окончания гарантии *
                    </label>
                    <input type="date" name="warranty_end_date" id="warranty_end_date"
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                           value="{{ old('warranty_end_date', $equipment->warranty_end_date ? $equipment->warranty_end_date->format('Y-m-d') : '') }}"
                           {{ old('has_warranty', $equipment->has_warranty) ? 'required' : '' }}>
                </div>

                <div class="mb-4">
                    <label for="service_comment" class="block text-gray-700 text-sm font-bold mb-2">
                        Комментарий о проведённом обслуживании
                    </label>
                    <textarea name="service_comment" id="service_comment" rows="3"
                              class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">{{ old('service_comment', $equipment->service_comment) }}</textarea>
                </div>

                <div class="mb-6">
                    <label for="known_issues" class="block text-gray-700 text-sm font-bold mb-2">
                        Известные проблемы
                    </label>
                    <textarea name="known_issues" id="known_issues" rows="3"
                              class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">{{ old('known_issues', $equipment->known_issues) }}</textarea>
                </div>

                <div class="flex items-center justify-between">
                    <button type="submit"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Обновить
                    </button>
                    <a href="{{ route('equipment.index') }}"
                       class="text-gray-600 hover:text-gray-800 font-medium">
                        Отмена
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // JavaScript для фильтрации кабинетов по локации был удален,
    // так как поле локации больше не используется

    // Инициализация поля гарантии
    toggleWarrantyDateField();
});

// Функция для отображения/скрытия поля даты окончания гарантии
function toggleWarrantyDateField() {
    const hasWarranty = document.getElementById('has_warranty').checked;
    const dateContainer = document.getElementById('warranty_date_container');
    const dateField = document.getElementById('warranty_end_date');

    if (hasWarranty) {
        dateContainer.classList.remove('hidden');
        dateField.setAttribute('required', 'required');
    } else {
        dateContainer.classList.add('hidden');
        dateField.removeAttribute('required');
    }
}
</script>
@endpush

@endsection
