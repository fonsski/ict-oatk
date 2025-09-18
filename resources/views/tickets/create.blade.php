@extends('layouts.app')

@section('title', 'Подать заявку - ICT')

@section('content')
<div class="container-width section-padding">
    <div class="max-w-4xl mx-auto">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-slate-900 mb-4">Подать заявку в техподдержку</h1>
            <p class="text-slate-600">
                Опишите вашу проблему подробно, и мы поможем её решить
            </p>
        </div>

        <div class="card p-8">
            <form action="{{ route('tickets.store') }}" method="POST" class="space-y-6">
                @csrf

                @if ($errors->any())
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 animate-fade-in">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-red-500 animate-pulse-once" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
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
                        maxlength="60"
                        minlength="5"
                        onkeyup="updateTitleCounter(this)"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <div class="flex justify-between mt-1">
                        <p class="text-sm text-gray-500">
                            Минимум 5, максимум 60 символов
                        </p>
                        <div id="titleCharCounter" class="text-xs text-gray-500 font-medium">0/60 символов</div>
                    </div>
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

                <!-- Информация о местоположении -->
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-900">Местоположение проблемы</h3>
                    <div>
                        <label for="room_id" class="block text-sm font-medium text-gray-700 mb-1">Кабинет</label>
                        <div class="relative">
                            <div class="flex mb-2">
                                <div class="relative flex-grow">
                                    <input type="text" id="room_search"
                                        placeholder="Поиск кабинета по номеру или названию..."
                                        maxlength="50"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="11" cy="11" r="8"></circle>
                                            <path d="M21 21l-4.35-4.35"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            <select id="room_id" name="room_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Не указано</option>
                                @foreach(\App\Models\Room::active()->orderBy('number')->get() as $room)
                                <option value="{{ $room->id }}"
                                    {{ old('room_id') == $room->id || (isset($userResponsibleRoom) && $userResponsibleRoom && $userResponsibleRoom->id == $room->id) ? 'selected' : '' }}
                                    data-number="{{ $room->number }}"
                                    data-name="{{ $room->name ?? $room->type_name }}"
                                    data-building="{{ $room->building }}"
                                    data-floor="{{ $room->floor }}"
                                    @if(isset($userResponsibleRoom) && $userResponsibleRoom && $userResponsibleRoom->id == $room->id)
                                        style="font-weight: bold; background-color: #EFF6FF; color: #1E40AF;"
                                    @endif
                                    >
                                    {{ $room->number }} - {{ $room->name ?? $room->type_name }}
                                    @if($room->building || $room->floor)
                                        ({{ $room->full_address }})
                                    @endif
                                    @if(isset($userResponsibleRoom) && $userResponsibleRoom && $userResponsibleRoom->id == $room->id)
                                        [✓ Вы ответственный]
                                    @endif
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <p class="mt-1 text-sm text-gray-500">
                            Найдите кабинет по номеру или названию или выберите из списка
                            @if(isset($userResponsibleRoom) && $userResponsibleRoom)
                                <br><span class="inline-flex items-center mt-1 text-blue-600 font-medium">
                                    <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    Автоматически выбран кабинет, за который вы ответственны
                                </span>
                            @endif
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
                    <button type="submit" id="submit-button"
                        class="w-full flex justify-center items-center px-4 py-3 border border-transparent rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-300 hover:shadow-lg">
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
// Функция для обновления счетчика символов в заголовке
function updateTitleCounter(input) {
    const charCounter = document.getElementById('titleCharCounter');
    const currentLength = input.value.length;
    charCounter.textContent = currentLength + '/60 символов';

    // Меняем цвет счетчика, если приближаемся к лимиту
    if (currentLength > 50 && currentLength < 60) {
        charCounter.classList.remove('text-gray-500', 'text-red-500');
        charCounter.classList.add('text-orange-500');
    } else if (currentLength >= 60) {
        charCounter.classList.remove('text-gray-500', 'text-orange-500');
        charCounter.classList.add('text-red-500');
    } else {
        charCounter.classList.remove('text-orange-500', 'text-red-500');
        charCounter.classList.add('text-gray-500');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Инициализируем счетчик заголовка при загрузке страницы
    const titleInput = document.getElementById('title');
    if (titleInput && titleInput.value.length > 0) {
        updateTitleCounter(titleInput);
    }
    // Защита от многократной отправки формы
    const form = document.querySelector('form');
    const submitButton = document.getElementById('submit-button');
    const phoneInput = document.getElementById('reporter_phone');

    // Инициализируем маску телефона вручную
    if (phoneInput) {
        const setupPhoneMask = function() {
            const mask = IMask(phoneInput, {
                mask: '+7 (000) 000-00-00',
                lazy: false,
                placeholderChar: '_',
                overwrite: true
            });

            // Автоматически добавляем +7 при фокусе, если поле пустое
            phoneInput.addEventListener('focus', function() {
                if (!this.value) {
                    mask.value = '+7 ';
                }
            });

            // Разрешаем удаление содержимого
            phoneInput.addEventListener('keydown', function(e) {
                if ((e.key === 'Backspace' || e.key === 'Delete') &&
                    (this.value === '+7 ' || this.value === '+7 (')) {
                    this.value = '';
                    e.preventDefault();
                }
            });
        };

        // Загружаем библиотеку IMask если нужно
        if (typeof IMask !== 'undefined') {
            setupPhoneMask();
        } else {
            const script = document.createElement('script');
            script.src = 'https://unpkg.com/imask@6.4.3/dist/imask.min.js';
            script.onload = setupPhoneMask;
            document.head.appendChild(script);
        }
    }

    if (form && submitButton) {
        form.addEventListener('submit', function(e) {
            // Если форма невалидна, не блокируем кнопку
            if (!this.checkValidity()) {
                return;
            }

            // Если кнопка уже отключена, предотвращаем отправку
            if (submitButton.disabled) {
                e.preventDefault();
                return;
            }

            // Отключаем кнопку отправки
            submitButton.disabled = true;
            submitButton.classList.add('opacity-75', 'cursor-not-allowed');
            submitButton.innerHTML = '<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Отправка...';

            // Разблокируем кнопку через 10 секунд на случай ошибки
            setTimeout(function() {
                submitButton.disabled = false;
                submitButton.classList.remove('opacity-75', 'cursor-not-allowed');
                submitButton.innerHTML = '<svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"></path></svg> Отправить заявку';
            }, 10000);
        });
    }
    const roomSelect = document.getElementById('room_id');
    const roomSearch = document.getElementById('room_search');
    const equipmentSelect = document.getElementById('equipment_id');
    // Поиск по кабинетам и загрузка оборудования происходит независимо от маски телефона

    // Улучшенный поиск по кабинетам
    roomSearch.addEventListener('input', function() {
        const searchText = this.value.toLowerCase().trim();
        const options = roomSelect.options;

        // Убедимся, что опция "Не указано" всегда видна
        options[0].style.display = '';

        // Сохраняем текущий выбор
        const currentSelectedValue = roomSelect.value;
        let foundExactMatch = false;
        let firstMatchIndex = -1;

        for (let i = 1; i < options.length; i++) {
            const roomNumber = options[i].getAttribute('data-number') || '';
            const roomName = options[i].getAttribute('data-name') || '';
            const building = options[i].getAttribute('data-building') || '';
            const floor = options[i].getAttribute('data-floor') || '';

            const optionText = `${roomNumber} ${roomName} ${building} ${floor}`.toLowerCase();
            const isExactMatch = roomNumber.toLowerCase() === searchText;

            if (isExactMatch) {
                foundExactMatch = true;
            }

            // Проверяем, содержит ли опция текст поиска или пуст ли поиск
            if (optionText.includes(searchText) || !searchText) {
                options[i].style.display = '';

                // Запоминаем индекс первого совпадения
                if (firstMatchIndex === -1 && searchText) {
                    firstMatchIndex = i;
                }
            } else {
                options[i].style.display = 'none';
            }
        }

        // Показать сообщение, если ничего не найдено
        const visibleOptions = Array.from(options).filter(opt => opt.style.display !== 'none' && opt.value !== '');
        const noResultsMsg = document.getElementById('no-results-message');

        if (visibleOptions.length === 0 && searchText) {
            if (!noResultsMsg) {
                const message = document.createElement('div');
                message.id = 'no-results-message';
                message.className = 'text-sm text-gray-500 mt-2';
                message.textContent = 'По данному запросу ничего не найдено';
                roomSelect.parentNode.appendChild(message);
            }
        } else if (noResultsMsg) {
            noResultsMsg.remove();
        }

        // Убрали автоматический выбор кабинета - пользователь должен выбрать вручную
    });

    // Слушаем изменения в выборе кабинета напрямую
    roomSelect.addEventListener('change', function() {
        loadEquipmentByRoom(this.value);
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
