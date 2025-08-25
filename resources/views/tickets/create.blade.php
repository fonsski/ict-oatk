@extends('layouts.app')

@section('title', 'Подать заявку - ICT')

@section('content')
<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-4">Подать заявку в техподдержку</h1>
            <p class="text-gray-600">
                Опишите вашу проблему подробно, и мы поможем её решить
            </p>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
            <form action="{{ route('tickets.store') }}" method="POST" class="space-y-6">
                @csrf

                @if ($errors->any())
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Обнаружены ошибки при заполнении формы:</h3>
                            <div class="mt-2 text-sm text-red-700">
                                <ul class="list-disc pl-5 space-y-1">
                                    @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                @if (session('success'))
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Заголовок заявки -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                        Заголовок заявки
                    </label>
                    <input type="text"
                        id="title"
                        name="title"
                        value="{{ old('title') }}"
                        required
                        maxlength="255"
                        minlength="5"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <p class="mt-1 text-sm text-gray-500">
                        Минимум 5, максимум 255 символов
                    </p>
                </div>

                <!-- Категория -->
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-1">
                        Категория
                    </label>
                    <select id="category"
                        name="category"
                        required
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="hardware" {{ old('category') == 'hardware' ? 'selected' : '' }}>Оборудование</option>
                        <option value="software" {{ old('category') == 'software' ? 'selected' : '' }}>Программное обеспечение</option>
                        <option value="network" {{ old('category') == 'network' ? 'selected' : '' }}>Сеть и интернет</option>
                        <option value="account" {{ old('category') == 'account' ? 'selected' : '' }}>Учетная запись</option>
                        <option value="other" {{ old('category') == 'other' ? 'selected' : '' }}>Другое</option>
                    </select>
                </div>

                <!-- Приоритет -->
                <div>
                    <label for="priority" class="block text-sm font-medium text-gray-700 mb-1">
                        Приоритет
                    </label>
                    <select id="priority"
                        name="priority"
                        required
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }} class="text-green-600">Низкий</option>
                        <option value="medium" {{ old('priority') == 'medium' ? 'selected' : '' }} class="text-yellow-600">Средний</option>
                        <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }} class="text-orange-600">Высокий</option>
                        <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }} class="text-red-600">Срочный</option>
                    </select>
                </div>

                <!-- Описание проблемы -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                        Описание проблемы
                    </label>
                    <textarea id="description"
                        name="description"
                        rows="6"
                        required
                        maxlength="5000"
                        minlength="10"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description') }}</textarea>
                    <p class="mt-1 text-sm text-gray-500">
                        Пожалуйста, опишите вашу проблему как можно подробнее (минимум 10, максимум 5000 символов)
                    </p>
                </div>

                <!-- Контактная информация -->
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-900">Контактная информация</h3>

                    <div>
                        <label for="reporter_name" class="block text-sm font-medium text-gray-700 mb-1">
                            ФИО
                        </label>
                        <input type="text"
                            id="reporter_name"
                            name="reporter_name"
                            value="{{ old('reporter_name', auth()->user()->name ?? '') }}"
                            required
                            maxlength="255"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="reporter_phone" class="block text-sm font-medium text-gray-700 mb-1">
                            Номер телефона
                        </label>
                        <input type="tel"
                            id="reporter_phone"
                            name="reporter_phone"
                            value="{{ old('reporter_phone', auth()->user()->phone ?? '') }}"
                            placeholder="+7 (___) ___-__-__"
                            maxlength="20"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p class="mt-1 text-sm text-gray-500">
                            Формат: +7 (999) 999-99-99
                        </p>
                    </div>
                    <div>
                        <label for="room_id" class="block text-sm font-medium text-gray-700 mb-1">Кабинет</label>
                        <div class="relative">
                            <input type="text" id="room_search"
                                placeholder="Поиск кабинета по номеру..."
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 mb-2">
                            <select id="room_id" name="room_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Не указано</option>
                                @foreach(\App\Models\Room::active()->orderBy('number')->get() as $room)
                                <option value="{{ $room->id }}" {{ old('room_id') == $room->id ? 'selected' : '' }} data-number="{{ $room->number }}" data-name="{{ $room->name ?? $room->type_name }}">
                                    {{ $room->number }} - {{ $room->name ?? $room->type_name }}
                                    @if($room->building || $room->floor)
                                        ({{ $room->full_address }})
                                    @endif
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <p class="mt-1 text-sm text-gray-500">
                            Найдите кабинет по номеру или выберите из списка
                        </p>
                    </div>

                    <div>
                        <label for="equipment_id" class="block text-sm font-medium text-gray-700 mb-1">Оборудование</label>
                        <select id="equipment_id" name="equipment_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Не указано</option>
                        </select>
                        <p class="mt-1 text-sm text-gray-500">
                            Выберите оборудование, с которым возникла проблема (необязательно)
                        </p>
                    </div>
                </div>

                <!-- Кнопка отправки -->
                <div class="pt-4">
                    <button type="submit"
                        class="w-full flex justify-center items-center px-4 py-3 border border-transparent rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"></path>
                        </svg>
                        Отправить заявку
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const roomSelect = document.getElementById('room_id');
    const roomSearch = document.getElementById('room_search');
    const equipmentSelect = document.getElementById('equipment_id');
    const phoneInput = document.getElementById('reporter_phone');

    // Инициализация маски для телефона
    function initPhoneMask() {
        if (!phoneInput) return;

        const maskOptions = {
            mask: '+7 (000) 000-00-00',
            lazy: false
        };

        const mask = IMask(phoneInput, maskOptions);

        // Автоматически добавляем +7 при фокусе, если поле пустое
        phoneInput.addEventListener('focus', function() {
            if (!this.value) {
                mask.value = '+7 ';
            }
        });
    }

    // Загружаем библиотеку IMask динамически
    function loadIMask() {
        if (window.IMask) {
            initPhoneMask();
            return;
        }

        const script = document.createElement('script');
        script.src = 'https://unpkg.com/imask@6.4.3/dist/imask.min.js';
        script.onload = initPhoneMask;
        document.head.appendChild(script);
    }

    // Загружаем маску для телефона
    loadIMask();

    // Поиск по кабинетам
    roomSearch.addEventListener('input', function() {
        const searchText = this.value.toLowerCase();
        const options = roomSelect.options;

        for (let i = 0; i < options.length; i++) {
            const roomNumber = options[i].getAttribute('data-number') || '';
            const roomName = options[i].getAttribute('data-name') || '';
            const optionText = (roomNumber + ' ' + roomName).toLowerCase();

            if (optionText.includes(searchText) || !searchText) {
                options[i].style.display = '';
            } else {
                options[i].style.display = 'none';
            }
        }
    });

    // Функция для загрузки оборудования по ID кабинета
    function loadEquipmentByRoom(roomId) {
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
