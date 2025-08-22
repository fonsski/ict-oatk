@extends('layouts.app')

@section('title', 'Управление кабинетами - ICT')

@section('content')
<div class="max-w-7xl mx-auto px-6 py-8">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-8">
        <div class="mb-6 lg:mb-0">
            <h1 class="text-3xl font-bold text-slate-900 mb-2">Управление кабинетами</h1>
            <p class="text-slate-600">Просмотр и управление учебными кабинетами и помещениями</p>
        </div>
        <a href="{{ route('room.create') }}"
            class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors duration-200">
            <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Добавить кабинет
        </a>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 12l2 2 4-4"></path>
                    <path d="M21 12c-1 0-2-1-2-2s1-2 2-2 2 1 2 2-1 2-2 2"></path>
                    <path d="M3 12c1 0 2-1 2-2s-1-2-2-2-2 1-2 2 1 2 2 2"></path>
                    <path d="M12 3c0 1-1 2-2 2s-2 1-2 2 1 2 2 2 2 1 2 2 1-2 2-2 2-1 2-2-1-2-2-2-2-1-2-2"></path>
                </svg>
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
                {{ session('error') }}
            </div>
        </div>
    @endif

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 mb-6">
        <form method="GET" action="{{ route('room.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Search -->
                <div>
                    <label for="search" class="block text-sm font-medium text-slate-700 mb-1">Поиск</label>
                    <input type="text"
                           name="search"
                           id="search"
                           value="{{ request('search') }}"
                           placeholder="Номер, название, описание..."
                           class="block w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Status Filter -->
                <div>
                    <label for="status" class="block text-sm font-medium text-slate-700 mb-1">Статус</label>
                    <select name="status" id="status" class="block w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Все статусы</option>
                        @foreach($statuses as $key => $name)
                            <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Type Filter -->
                <div>
                    <label for="type" class="block text-sm font-medium text-slate-700 mb-1">Тип</label>
                    <select name="type" id="type" class="block w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Все типы</option>
                        @foreach($types as $key => $name)
                            <option value="{{ $key }}" {{ request('type') == $key ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Building Filter -->
                <div>
                    <label for="building" class="block text-sm font-medium text-slate-700 mb-1">Здание</label>
                    <select name="building" id="building" class="block w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Все здания</option>
                        @foreach($buildings as $building)
                            <option value="{{ $building }}" {{ request('building') == $building ? 'selected' : '' }}>
                                {{ $building }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <!-- Floor Filter -->
                <div>
                    <label for="floor" class="block text-sm font-medium text-slate-700 mb-1">Этаж</label>
                    <select name="floor" id="floor" class="block w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Все этажи</option>
                        @foreach($floors as $floor)
                            <option value="{{ $floor }}" {{ request('floor') == $floor ? 'selected' : '' }}>
                                {{ $floor }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Active Filter -->
                <div>
                    <label for="active" class="block text-sm font-medium text-slate-700 mb-1">Активность</label>
                    <select name="active" id="active" class="block w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Все</option>
                        <option value="true" {{ request('active') == 'true' ? 'selected' : '' }}>Активные</option>
                        <option value="false" {{ request('active') == 'false' ? 'selected' : '' }}>Неактивные</option>
                    </select>
                </div>

                <!-- Filter Actions -->
                <div class="md:col-span-2 flex items-end space-x-2">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        Поиск
                    </button>
                    <a href="{{ route('room.index') }}" class="inline-flex items-center px-4 py-2 bg-slate-200 text-slate-700 font-medium rounded-lg hover:bg-slate-300 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 6h18l-2 13H5L3 6z"></path>
                            <path d="m19 6-3-3H8L5 6"></path>
                        </svg>
                        Сбросить
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Rooms List -->
    @if($rooms->count())
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                Кабинет
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                Тип
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                Расположение
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                Статус
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                Вместимость
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                Оборудование
                            </th>
                            <th scope="col" class="relative px-6 py-3">
                                <span class="sr-only">Действия</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        @foreach($rooms as $room)
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <div class="flex items-center">
                                            <div class="text-sm font-medium text-slate-900 mb-1">
                                                {{ $room->number }} - {{ $room->name }}
                                            </div>
                                            @if(!$room->is_active)
                                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    Неактивен
                                                </span>
                                            @endif
                                        </div>
                                        @if($room->description)
                                            <div class="text-sm text-slate-500">
                                                {{ Str::limit($room->description, 50) }}
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    {!! $room->type_badge !!}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-slate-900">{{ $room->full_address }}</div>
                                    @if($room->responsible_person)
                                        <div class="text-sm text-slate-500">{{ $room->responsible_person }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    {!! $room->status_badge !!}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                                    {{ $room->capacity ? $room->capacity . ' мест' : '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                    {{ $room->equipment->count() }} шт.
                                    @if($room->equipment->count() > 0)
                                        <span class="text-green-600">({{ $room->equipment->where('status', 'active')->count() }} активных)</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-2">
                                        <!-- View -->
                                        <a href="{{ route('room.show', $room) }}"
                                           class="text-blue-600 hover:text-blue-900 transition-colors duration-200"
                                           title="Просмотреть">
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                        </a>

                                        <!-- Edit -->
                                        <a href="{{ route('room.edit', $room) }}"
                                           class="text-indigo-600 hover:text-indigo-900 transition-colors duration-200"
                                           title="Редактировать">
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                        </a>

                                        <!-- Status Change -->
                                        <div class="relative">
                                            <button type="button"
                                                    class="text-green-600 hover:text-green-900 transition-colors duration-200 status-menu-button"
                                                    title="Изменить статус"
                                                    data-room-id="{{ $room->id }}">
                                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="1"></circle>
                                                    <circle cx="12" cy="5" r="1"></circle>
                                                    <circle cx="12" cy="19" r="1"></circle>
                                                </svg>
                                            </button>

                                            <div class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg border border-gray-200 z-10 status-dropdown" data-room-id="{{ $room->id }}">
                                                <div class="py-1">
                                                    @foreach(\App\Models\Room::STATUSES as $status => $statusName)
                                                        @if($status !== $room->status)
                                                            <form action="{{ route('room.change-status', $room) }}" method="POST" class="inline">
                                                                @csrf
                                                                <input type="hidden" name="status" value="{{ $status }}">
                                                                <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                                    {{ $statusName }}
                                                                </button>
                                                            </form>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Toggle Active -->
                                        <form action="{{ route('room.toggle-active', $room) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit"
                                                    class="{{ $room->is_active ? 'text-orange-600 hover:text-orange-900' : 'text-green-600 hover:text-green-900' }} transition-colors duration-200"
                                                    title="{{ $room->is_active ? 'Деактивировать' : 'Активировать' }}">
                                                @if($room->is_active)
                                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <circle cx="12" cy="12" r="3"></circle>
                                                        <path d="M12 1v6m0 6v6m11-7h-6m-6 0H1"></path>
                                                    </svg>
                                                @else
                                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M9 12l2 2 4-4"></path>
                                                        <path d="M21 12c-1 0-2-1-2-2s1-2 2-2 2 1 2 2-1 2-2 2"></path>
                                                        <path d="M3 12c1 0 2-1 2-2s-1-2-2-2-2 1-2 2 1 2 2 2"></path>
                                                        <path d="M12 3c0 1-1 2-2 2s-2 1-2 2 1 2 2 2 2 1 2 2 1-2 2-2 2-1 2-2-1-2-2-2-2-1-2-2"></path>
                                                    </svg>
                                                @endif
                                            </button>
                                        </form>

                                        <!-- Delete -->
                                        <form action="{{ route('room.destroy', $room) }}" method="POST" class="inline"
                                              onsubmit="return confirm('Вы уверены, что хотите удалить этот кабинет?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="text-red-600 hover:text-red-900 transition-colors duration-200"
                                                    title="Удалить">
                                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="3,6 5,6 21,6"></polyline>
                                                    <path d="M19,6v14a2,2 0 0,1 -2,2H7a2,2 0 0,1 -2,-2V6m3,0V4a2,2 0 0,1 2,-2h4a2,2 0 0,1 2,2v2"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        @if($rooms->hasPages())
            <div class="mt-6">
                {{ $rooms->appends(request()->query())->links() }}
            </div>
        @endif

        <!-- Stats -->
        <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
                <div class="text-2xl font-bold text-slate-900">{{ $rooms->total() }}</div>
                <div class="text-sm text-slate-600">Всего кабинетов</div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
                <div class="text-2xl font-bold text-green-600">{{ $rooms->where('is_active', true)->count() }}</div>
                <div class="text-sm text-slate-600">Активных</div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
                <div class="text-2xl font-bold text-blue-600">{{ $rooms->where('status', 'available')->count() }}</div>
                <div class="text-sm text-slate-600">Доступно</div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
                <div class="text-2xl font-bold text-orange-600">{{ $rooms->where('status', 'occupied')->count() }}</div>
                <div class="text-sm text-slate-600">Занято</div>
            </div>
        </div>

    @else
        <!-- Empty State -->
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-12 text-center">
            <div class="mx-auto w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 21h18"></path>
                    <path d="M5 21V7l8-4v18"></path>
                    <path d="M19 21V11l-6-4"></path>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-slate-900 mb-2">Кабинеты не найдены</h3>
            <p class="text-slate-600 mb-6">Нет кабинетов, соответствующих критериям поиска</p>
            @if(request()->hasAny(['search', 'status', 'type', 'building', 'floor', 'active']))
                <a href="{{ route('room.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-slate-200 text-slate-700 font-medium rounded-lg hover:bg-slate-300 transition-colors duration-200">
                    Сбросить фильтры
                </a>
            @else
                <a href="{{ route('room.create') }}"
                    class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors duration-200">
                    <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Создать первый кабинет
                </a>
            @endif
        </div>
    @endif
</div>

<script>
// Status dropdown functionality and live search
document.addEventListener('DOMContentLoaded', function() {
    const statusButtons = document.querySelectorAll('.status-menu-button');
    const statusDropdowns = document.querySelectorAll('.status-dropdown');

    statusButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const roomId = this.dataset.roomId;
            const dropdown = document.querySelector(`.status-dropdown[data-room-id="${roomId}"]`);

            // Close all other dropdowns
            statusDropdowns.forEach(function(d) {
                if (d !== dropdown) {
                    d.classList.add('hidden');
                }
            });

            // Toggle current dropdown
            dropdown.classList.toggle('hidden');
        });
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.status-menu-button')) {
            statusDropdowns.forEach(function(dropdown) {
                dropdown.classList.add('hidden');
            });
        }
    });

    // Live search functionality
    const searchInput = document.querySelector('input[name="search"]');
    const statusSelect = document.querySelector('select[name="status"]');
    const typeSelect = document.querySelector('select[name="type"]');
    const buildingSelect = document.querySelector('select[name="building"]');
    const floorSelect = document.querySelector('select[name="floor"]');
    const activeSelect = document.querySelector('select[name="active"]');
    const form = document.querySelector('form');

    let searchTimeout;

    // Функция для выполнения поиска
    function performSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            // Показать индикатор загрузки
            showLoadingIndicator();

            // Отправить форму
            const formData = new FormData(form);
            const params = new URLSearchParams(formData);

            fetch(window.location.pathname + '?' + params.toString())
                .then(response => response.text())
                .then(html => {
                    // Создать временный элемент для парсинга HTML
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = html;

                    // Найти новый контент в ответе
                    const newRoomsGrid = tempDiv.querySelector('.grid.grid-cols-1.md\\:grid-cols-2.lg\\:grid-cols-3.xl\\:grid-cols-4.gap-6');
                    const currentRoomsGrid = document.querySelector('.grid.grid-cols-1.md\\:grid-cols-2.lg\\:grid-cols-3.xl\\:grid-cols-4.gap-6');

                    if (newRoomsGrid && currentRoomsGrid) {
                        currentRoomsGrid.innerHTML = newRoomsGrid.innerHTML;
                        reinitializeDropdowns();
                    }

                    // Обновить пагинацию
                    const newPagination = tempDiv.querySelector('.mt-8');
                    const currentPagination = document.querySelector('.mt-8');

                    if (newPagination && currentPagination) {
                        currentPagination.innerHTML = newPagination.innerHTML;
                    }

                    hideLoadingIndicator();
                })
                .catch(error => {
                    console.error('Ошибка поиска:', error);
                    hideLoadingIndicator();
                });
        }, 300); // Задержка 300ms для избежания избыточных запросов
    }

    function showLoadingIndicator() {
        const grid = document.querySelector('.grid.grid-cols-1.md\\:grid-cols-2.lg\\:grid-cols-3.xl\\:grid-cols-4.gap-6');
        if (grid) {
            grid.style.opacity = '0.5';
        }
    }

    function hideLoadingIndicator() {
        const grid = document.querySelector('.grid.grid-cols-1.md\\:grid-cols-2.lg\\:grid-cols-3.xl\\:grid-cols-4.gap-6');
        if (grid) {
            grid.style.opacity = '1';
        }
    }

    function reinitializeDropdowns() {
        // Переинициализировать dropdown'ы после обновления содержимого
        const newStatusButtons = document.querySelectorAll('.status-menu-button');
        const newStatusDropdowns = document.querySelectorAll('.status-dropdown');

        newStatusButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const roomId = this.dataset.roomId;
                const dropdown = document.querySelector(`.status-dropdown[data-room-id="${roomId}"]`);

                // Close all other dropdowns
                newStatusDropdowns.forEach(function(d) {
                    if (d !== dropdown) {
                        d.classList.add('hidden');
                    }
                });

                // Toggle current dropdown
                dropdown.classList.toggle('hidden');
            });
        });
    }

    // Добавить обработчики событий для живого поиска
    if (searchInput) {
        searchInput.addEventListener('input', performSearch);
    }

    if (statusSelect) {
        statusSelect.addEventListener('change', performSearch);
    }

    if (typeSelect) {
        typeSelect.addEventListener('change', performSearch);
    }

    if (buildingSelect) {
        buildingSelect.addEventListener('change', performSearch);
    }

    if (floorSelect) {
        floorSelect.addEventListener('change', performSearch);
    }

    if (activeSelect) {
        activeSelect.addEventListener('change', performSearch);
    }

    // Обработчик для кнопки "Поиск" (оставляем для совместимости)
    const submitBtn = document.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.addEventListener('click', function(e) {
            e.preventDefault();
            performSearch();
        });
    }
});
</script>
@endsection
