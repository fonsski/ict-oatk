@extends('layouts.app')

@section('title', 'Добавить оборудование')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white shadow-md rounded-lg p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Добавить оборудование</h1>

            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('equipment.store') }}" method="POST">
                @csrf

                <div class="mb-4">
                    <label for="inventory_number" class="block text-gray-700 text-sm font-bold mb-2">
                        Инвентарный номер *
                    </label>
                    <input type="text" name="inventory_number" id="inventory_number"
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('inventory_number') border-red-500 @enderror"
                           value="{{ old('inventory_number') }}" 
                           required 
                           minlength="1" 
                           maxlength="20"
                           data-char-counter
                           data-max-length="20"
                           data-min-length="1"
                           data-warning-threshold="15"
                           data-help-text="Минимум 1, максимум 20 цифр"
                           placeholder="Например: 123456789">
                    <div class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-md">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-blue-800">Инвентарный номер</h3>
                                <div class="mt-2 text-sm text-blue-700">
                                    <p class="mb-2">Номер, выданный бухгалтерией колледжа. Содержит только цифры.</p>
                                    <p class="font-medium">Примеры: 123456789, 987654321, 555666777</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    @error('inventory_number')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="accounting_number" class="block text-gray-700 text-sm font-bold mb-2">
                        Учётный номер
                    </label>
                    <input type="text" name="accounting_number" id="accounting_number"
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('accounting_number') border-red-500 @enderror"
                           value="{{ old('accounting_number') }}" 
                           minlength="3" 
                           maxlength="20"
                           data-char-counter
                           data-max-length="20"
                           data-min-length="3"
                           data-warning-threshold="15"
                           data-help-text="Минимум 3, максимум 20 символов"
                           placeholder="Например: А1-студент-001">
                    <div class="mt-2 p-3 bg-green-50 border border-green-200 rounded-md">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-green-800">Формат учётного номера</h3>
                                <div class="mt-2 text-sm text-green-700">
                                    <p class="mb-2">Используйте следующий формат: <strong>КодЗданияЭтаж-Группа-Номер</strong></p>
                                    <div class="space-y-1">
                                        <p><strong>Код здания:</strong> А, Б</p>
                                        <p><strong>Этаж:</strong> 1, 2, 3, 4, 5</p>
                                        <p><strong>Группа:</strong> студент, преподаватель, администрация, сотрудник</p>
                                        <p><strong>Номер:</strong> последовательность цифр (001, 002, 003...)</p>
                                    </div>
                                    <p class="mt-2 font-medium">Примеры: А1-студент-001, Б3-преподаватель-005, А2-сотрудник-012, Б4-администрация-003</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    @error('accounting_number')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="category_id" class="block text-gray-700 text-sm font-bold mb-2">
                        Категория
                    </label>
                    <select name="category_id" id="category_id"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="">Выберите категорию</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
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
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('name') border-red-500 @enderror"
                           value="{{ old('name') }}" 
                           minlength="2" 
                           maxlength="255"
                           data-char-counter
                           data-max-length="255"
                           data-min-length="2"
                           data-warning-threshold="200"
                           data-help-text="Минимум 2, максимум 255 символов">
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="status_id" class="block text-gray-700 text-sm font-bold mb-2">
                        Статус *
                    </label>
                    <select name="status_id" id="status_id"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('status_id') border-red-500 @enderror"
                            required>
                        <option value="">Выберите статус</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status->id }}" {{ old('status_id') == $status->id ? 'selected' : '' }}>
                                {{ $status->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('status_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Поле location_id было удалено, так как оно не используется в системе -->

                <div class="mb-4">
                    <label for="room_id" class="block text-gray-700 text-sm font-bold mb-2">
                        Текущий кабинет
                    </label>
                    <select name="room_id" id="room_id"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('room_id') border-red-500 @enderror">
                        <option value="">Выберите кабинет</option>
                        @foreach($rooms as $room)
                            <option value="{{ $room->id }}" {{ old('room_id') == $room->id ? 'selected' : '' }}">
                                {{ $room->number }} - {{ $room->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('room_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="initial_room_id" class="block text-gray-700 text-sm font-bold mb-2">
                        Начальный кабинет
                    </label>
                    <select name="initial_room_id" id="initial_room_id"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('initial_room_id') border-red-500 @enderror">
                        <option value="">Такой же как текущий</option>
                        @foreach($rooms as $room)
                            <option value="{{ $room->id }}" {{ old('initial_room_id') == $room->id ? 'selected' : '' }}">
                                {{ $room->number }} - {{ $room->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('initial_room_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-sm text-gray-500 mt-1">Если не указан, будет использован текущий кабинет</p>
                </div>

                <div class="mb-4">
                    <label for="has_warranty" class="flex items-center text-gray-700 text-sm font-bold mb-2">
                        <input type="checkbox" name="has_warranty" id="has_warranty" value="1"
                               class="mr-2" {{ old('has_warranty') ? 'checked' : '' }}
                               onchange="toggleWarrantyDateField()">
                        Гарантия
                    </label>
                </div>

                <div id="warranty_date_container" class="mb-4 {{ old('has_warranty') ? '' : 'hidden' }}">
                    <label for="warranty_end_date" class="block text-gray-700 text-sm font-bold mb-2">
                        Дата окончания гарантии *
                    </label>
                    <input type="date" name="warranty_end_date" id="warranty_end_date"
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('warranty_end_date') border-red-500 @enderror"
                           value="{{ old('warranty_end_date') }}" {{ old('has_warranty') ? 'required' : '' }}>
                    @error('warranty_end_date')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="service_comment" class="block text-gray-700 text-sm font-bold mb-2">
                        Комментарий о проведённом обслуживании
                    </label>
                    <textarea name="service_comment" id="service_comment" rows="3"
                              class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('service_comment') border-red-500 @enderror"
                              minlength="5" maxlength="500">{{ old('service_comment') }}</textarea>
                    @error('service_comment')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6">
                    <label for="known_issues" class="block text-gray-700 text-sm font-bold mb-2">
                        Известные проблемы
                    </label>
                    <textarea name="known_issues" id="known_issues" rows="3"
                              class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('known_issues') border-red-500 @enderror"
                              minlength="5" maxlength="500">{{ old('known_issues') }}</textarea>
                    @error('known_issues')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between">
                    <button type="submit"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Сохранить
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
