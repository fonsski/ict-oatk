@extends('layouts.app')

@section('title', 'Редактировать заявку - ICT')

@section('content')
<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-4">Редактирование заявки</h1>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
            <form action="{{ route('tickets.update', $ticket) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                @if ($errors->any())
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                    <div class="ml-3">
                        <div class="mt-2 text-sm text-red-700">
                            <ul class="list-disc pl-5 space-y-1">
                                @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
                @endif

                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Заголовок заявки</label>
                    <input type="text" 
                           id="title" 
                           name="title" 
                           value="{{ old('title', $ticket->title) }}" 
                           required 
                           maxlength="60"
                           minlength="5"
                           data-char-counter
                           data-max-length="60"
                           data-min-length="5"
                           data-warning-threshold="50"
                           data-help-text="Минимум 5, максимум 60 символов"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Категория</label>
                    <select id="category" name="category" required class="block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="hardware" {{ old('category', $ticket->category) == 'hardware' ? 'selected' : '' }}>Оборудование</option>
                        <option value="software" {{ old('category', $ticket->category) == 'software' ? 'selected' : '' }}>Программное обеспечение</option>
                        <option value="network" {{ old('category', $ticket->category) == 'network' ? 'selected' : '' }}>Сеть и интернет</option>
                        <option value="account" {{ old('category', $ticket->category) == 'account' ? 'selected' : '' }}>Учетная запись</option>
                        <option value="other" {{ old('category', $ticket->category) == 'other' ? 'selected' : '' }}>Другое</option>
                    </select>
                </div>

                <div>
                    <label for="priority" class="block text-sm font-medium text-gray-700 mb-1">Приоритет</label>
                    <select id="priority" name="priority" required class="block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="low" {{ old('priority', $ticket->priority) == 'low' ? 'selected' : '' }}>Низкий</option>
                        <option value="medium" {{ old('priority', $ticket->priority) == 'medium' ? 'selected' : '' }}>Средний</option>
                        <option value="high" {{ old('priority', $ticket->priority) == 'high' ? 'selected' : '' }}>Высокий</option>
                        <option value="urgent" {{ old('priority', $ticket->priority) == 'urgent' ? 'selected' : '' }}>Срочный</option>
                    </select>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Описание проблемы</label>
                    <textarea id="description" name="description" rows="6" required class="block w-full rounded-md border-gray-300 shadow-sm">{{ old('description', $ticket->description) }}</textarea>
                </div>

                <div>
                    <label for="reporter_name" class="block text-sm font-medium text-gray-700 mb-1">ФИО заявителя</label>
                    <input type="text" id="reporter_name" name="reporter_name" value="{{ old('reporter_name', $ticket->reporter_name) }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label for="reporter_phone" class="block text-sm font-medium text-gray-700 mb-1">Номер телефона</label>
                    <input type="tel" id="reporter_phone" name="reporter_phone" value="{{ old('reporter_phone', $ticket->reporter_phone) }}" placeholder="+7 (___) ___-__-__" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <!-- Поле email удалено, система не использует почту -->

                <div>
                    <label for="room_id" class="block text-sm font-medium text-gray-700 mb-1">Кабинет</label>
                    <select id="room_id" name="room_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Не указано</option>
                        @foreach(\App\Models\Room::active()->orderBy('number')->get() as $room)
                        <option value="{{ $room->id }}" {{ old('room_id', $ticket->room_id) == $room->id ? 'selected' : '' }}>
                            {{ $room->number }} - {{ $room->name ?? $room->type_name }}
                            @if($room->building || $room->floor)
                                ({{ $room->full_address }})
                            @endif
                        </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-sm text-gray-500">
                        Выберите кабинет, где возникла проблема
                    </p>
                </div>

                <div>
                    <label for="equipment_id" class="block text-sm font-medium text-gray-700 mb-1">Оборудование</label>
                    <select id="equipment_id" name="equipment_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Не указано</option>
                        @if($ticket->room_id)
                            @foreach(\App\Models\Equipment::where('room_id', $ticket->room_id)->get() as $equipment)
                            <option value="{{ $equipment->id }}" {{ old('equipment_id', $ticket->equipment_id) == $equipment->id ? 'selected' : '' }}>
                                {{ $equipment->name ?: 'Оборудование' }} ({{ $equipment->inventory_number }})
                            </option>
                            @endforeach
                        @endif
                    </select>
                    <p class="mt-1 text-sm text-gray-500">
                        Выберите оборудование, с которым возникла проблема (необязательно)
                    </p>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-3 border border-transparent rounded-md shadow-sm text-white bg-blue-600">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const roomSelect = document.getElementById('room_id');
    const equipmentSelect = document.getElementById('equipment_id');

    // Функция для загрузки оборудования по ID кабинета
    function loadEquipmentByRoom(roomId) {
        // Сохраняем текущее выбранное оборудование
        const currentEquipmentId = equipmentSelect.value;

        if (!roomId) {
            // Если кабинет не выбран, очищаем список оборудования
            equipmentSelect.innerHTML = '<option value="">Не указано</option>';
            return;
        }

        // Запрос к API для получения оборудования по кабинету
        fetch(`/api/equipment/by-room?room_id=${roomId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Очищаем текущие опции и добавляем пустую опцию
                    equipmentSelect.innerHTML = '<option value="">Не указано</option>';

                    // Добавляем оборудование из ответа API
                    data.data.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.id;
                        option.textContent = `${item.name || 'Без названия'} (${item.inventory_number})`;
                        // Если это было выбрано ранее, отмечаем как выбранное
                        if (item.id == currentEquipmentId) {
                            option.selected = true;
                        }
                        equipmentSelect.appendChild(option);
                    });

                    // Если оборудования нет, показываем сообщение
                    if (data.data.length === 0) {
                        const option = document.createElement('option');
                        option.value = '';
                        option.textContent = 'В этом кабинете нет оборудования';
                        option.disabled = true;
                        equipmentSelect.appendChild(option);
                    }
                }
            })
            .catch(error => {
                console.error('Ошибка при загрузке оборудования:', error);
                equipmentSelect.innerHTML = '<option value="">Ошибка загрузки оборудования</option>';
            });
    }

    // Загружаем оборудование при изменении кабинета
    roomSelect.addEventListener('change', function() {
        loadEquipmentByRoom(this.value);
    });

    // Загружаем оборудование при загрузке страницы, если кабинет уже выбран
    if (roomSelect.value) {
        loadEquipmentByRoom(roomSelect.value);
    }
});
</script>
@endpush

@endsection
