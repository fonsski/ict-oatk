@extends('layouts.app')

@section('title', 'Все заявки - ICT')

@section('content')
<div class="container-width section-padding">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 mb-2">Все заявки</h1>
            <p class="text-slate-600">Управление всеми заявками в системе</p>
        </div>
        <div class="flex items-center gap-4 mt-4 sm:mt-0">
            <div class="flex items-center gap-2 text-sm text-slate-600">
                <div class="w-2 h-2 bg-green-500 rounded-full" id="status-indicator"></div>
                <span id="last-updated">Загрузка...</span>
            </div>
            <button id="refresh-btn" class="btn-secondary">
                <svg class="w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="23,4 23,10 17,10"></polyline>
                    <path d="M20.49,15a9,9,0,1,1-2.12-9.36L23,10"></path>
                </svg>
                Обновить
            </button>
            <a href="{{ route('tickets.create') }}" class="btn-primary">
                Новая заявка
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4 lg:gap-6 mb-8" id="stats-cards">
        <div class="card p-6 text-center">
            <div class="text-2xl font-bold text-slate-900 mb-1" id="total-count">{{ $tickets->total() ?? 0 }}</div>
            <div class="text-sm text-slate-600">Всего</div>
        </div>
        <div class="card p-6 text-center">
            <div class="text-2xl font-bold text-blue-600 mb-1" id="open-count">{{ $tickets->where('status', 'open')->count() }}</div>
            <div class="text-sm text-slate-600">Открытые</div>
        </div>
        <div class="card p-6 text-center">
            <div class="text-2xl font-bold text-yellow-600 mb-1" id="progress-count">{{ $tickets->where('status', 'in_progress')->count() }}</div>
            <div class="text-sm text-slate-600">В работе</div>
        </div>
        <div class="card p-6 text-center">
            <div class="text-2xl font-bold text-green-600 mb-1" id="resolved-count">{{ $tickets->where('status', 'resolved')->count() }}</div>
            <div class="text-sm text-slate-600">Решённые</div>
        </div>
        <div class="card p-6 text-center">
            <div class="text-2xl font-bold text-slate-600 mb-1" id="closed-count">{{ $tickets->where('status', 'closed')->count() }}</div>
            <div class="text-sm text-slate-600">Закрытые</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card p-6 mb-8">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-slate-900">Фильтры</h3>
            <button type="button" id="toggle-filters" class="md:hidden btn-outline py-1 px-3 text-sm">
                <span class="show-text">Показать фильтры</span>
                <span class="hide-text hidden">Скрыть фильтры</span>
            </button>
        </div>
        <div class="filters-container md:block">
            <form id="filters-form" method="GET" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <!-- Большое поле поиска сверху -->
            <div class="sm:col-span-3 md:col-span-3 lg:col-span-6 mb-4">
                <label for="search" class="form-label">Поиск</label>
                <div class="relative">
                    <input type="text" id="search" name="search" value="{{ request('search') }}"
                       placeholder="Поиск по заявкам..." class="search-input">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-6 w-6 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="M21 21l-4.35-4.35"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div>
                <label for="status" class="form-label">Статус</label>
                <select id="status" name="status" class="form-input">
                    <option value="">Все статусы</option>
                    <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Открытые</option>
                    <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>В работе</option>
                    <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Решенные</option>
                    <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Закрытые</option>
                </select>
            </div>
            <div>
                <label for="priority" class="form-label">Приоритет</label>
                <select id="priority" name="priority" class="form-input">
                    <option value="">Все приоритеты</option>
                    <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Низкий</option>
                    <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>Средний</option>
                    <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>Высокий</option>
                    <option value="urgent" {{ request('priority') == 'urgent' ? 'selected' : '' }}>Срочный</option>
                </select>
            </div>
            <div>
                <label for="category" class="form-label">Категория</label>
                <select id="category" name="category" class="form-input">
                    <option value="">Все категории</option>
                    @foreach($categories ?? [] as $category)
                        <option value="{{ $category }}" {{ request('category') == $category ? 'selected' : '' }}>
                            {{ format_ticket_category($category) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="location_id" class="form-label">Расположение</label>
                <select id="location_id" name="location_id" class="form-input">
                    <option value="">Все расположения</option>
                    @foreach($locations ?? [] as $location)
                        <option value="{{ $location->id }}" {{ request('location_id') == $location->id ? 'selected' : '' }}>
                            {{ $location->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="room_id" class="form-label">Кабинет</label>
                <select id="room_id" name="room_id" class="form-input">
                    <option value="">Все кабинеты</option>
                    @foreach($rooms ?? [] as $room)
                        <option value="{{ $room->id }}" {{ request('room_id') == $room->id ? 'selected' : '' }}>
                            {{ $room->number }} - {{ $room->name ?: $room->type_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="assigned_to" class="form-label">Назначено</label>
                <select id="assigned_to" name="assigned_to" class="form-input">
                    <option value="">Все исполнители</option>
                    <option value="unassigned" {{ request('assigned_to') == 'unassigned' ? 'selected' : '' }}>Не назначено</option>
                    @foreach($assignable ?? [] as $user)
                        <option value="{{ $user->id }}" {{ request('assigned_to') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Кнопки под полем поиска -->
            <div class="sm:col-span-3 md:col-span-3 lg:col-span-6 flex flex-col sm:flex-row justify-center items-center gap-4 mt-4">
                <button type="button" id="clear-filters" class="btn-outline px-6 py-3 sm:w-1/3 w-full">
                    Сбросить
                </button>
                <button type="submit" class="btn-primary px-6 py-3 sm:w-1/3 w-full">
                    Применить фильтры
                </button>
            </div>
            </form>
        </div>
    </div>

    <!-- Tickets Table -->
    <div class="card">
        <div id="loading-indicator" class="hidden">
            <div class="flex items-center justify-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <span class="ml-4 text-slate-600">Загрузка заявок...</span>
            </div>
        </div>

        <!-- Bulk Actions Bar -->
        <div id="bulk-actions-bar" class="hidden items-center justify-between py-3 px-4 bg-blue-50 border border-blue-200 rounded-lg mb-4">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M7 9a2 2 0 012-2h6a2 2 0 012 2v6a2 2 0 01-2 2H9a2 2 0 01-2-2V9z" />
                    <path d="M5 3a2 2 0 00-2 2v6a2 2 0 002 2V5h8a2 2 0 00-2-2H5z" />
                </svg>
                <span class="font-medium text-blue-800">Выбрано заявок: <span id="selected-count">0</span></span>
            </div>
            <div class="flex items-center gap-2">
                <div class="relative inline-block text-left">
                    <button id="bulk-actions-button" type="button" class="flex items-center gap-1 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                        Действия
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div id="bulk-actions-menu" class="hidden absolute right-0 mt-2 w-56 bg-white rounded-md shadow-lg border border-gray-200 z-50">
                        <div class="py-1">
                            <button type="button" class="bulk-action-item block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" data-action="change-status" data-status="in_progress">Взять в работу</button>
                            <button type="button" class="bulk-action-item block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" data-action="change-status" data-status="resolved">Отметить как решенные</button>
                            <button type="button" class="bulk-action-item block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" data-action="change-status" data-status="closed">Закрыть заявки</button>
                            <button type="button" class="bulk-action-item block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" data-action="assign-to">Назначить исполнителя</button>
                        </div>
                    </div>
                </div>
                <button id="bulk-cancel-button" type="button" class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50 transition">
                    Отменить
                </button>
            </div>
        </div>

        <div id="tickets-container" class="overflow-x-auto rounded-lg border border-slate-200 mt-4">
            @if($tickets->count() > 0)
                <div class="w-full overflow-auto">
                    <table class="w-full table-auto">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="w-10 px-3 py-4">
                                    <div class="flex justify-center">
                                        <input type="checkbox" id="select-all-checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900 w-1/4">Заявка</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900 w-1/6">Заявитель</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900 w-24">Статус</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900 w-24">Приоритет</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900 w-1/6">Исполнитель</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900 w-24">Дата</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900 w-24">Действия</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200" id="tickets-tbody">
                            @include('tickets.partials.all-table-rows', ['tickets' => $tickets])
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-16" id="empty-state">
                    <div class="mx-auto w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-slate-900 mb-2">Заявок не найдено</h3>
                    <p class="text-slate-600 mb-6">Попробуйте изменить параметры фильтра или создайте новую заявку</p>
                    <a href="{{ route('tickets.create') }}" class="btn-primary btn-lg">
                        Создать заявку
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- Pagination -->
    @if($tickets->hasPages())
        <div class="mt-8 flex flex-col items-center" id="pagination-container">
            <div class="pagination-wrapper">
                {{ $tickets->links() }}
            </div>
        </div>
    @endif
</div>

<!-- Модальное окно для быстрых действий -->
<div id="quick-action-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black opacity-50"></div>
        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-slate-900" id="modal-title">Быстрое действие</h3>
                <button id="close-modal" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="modal-content">
                <!-- Содержимое модального окна будет загружено динамически -->
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let refreshInterval;
    const REFRESH_INTERVAL = 30000; // 30 секунд

    // Элементы
    const refreshBtn = document.getElementById('refresh-btn');
    const statusIndicator = document.getElementById('status-indicator');
    const lastUpdated = document.getElementById('last-updated');
    const loadingIndicator = document.getElementById('loading-indicator');
    const ticketsContainer = document.getElementById('tickets-container');
    const filtersForm = document.getElementById('filters-form');
    const clearFiltersBtn = document.getElementById('clear-filters');

    // Автоматическое обновление
    function startAutoRefresh() {
        refreshInterval = setInterval(refreshTickets, REFRESH_INTERVAL);
        console.log('Авто-обновление запущено с интервалом', REFRESH_INTERVAL, 'мс');
    }

    function stopAutoRefresh() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
            console.log('Авто-обновление остановлено');
        }
    }

    // Обновление заявок
    async function refreshTickets() {
        try {
            statusIndicator.className = 'w-2 h-2 bg-yellow-500 rounded-full';

            const formData = new FormData(filtersForm);
            const params = new URLSearchParams(formData);

            const response = await fetch(`{{ route('all-tickets.api') }}?${params}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                cache: 'no-store'
            });

            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

            const data = await response.json();

            updateStats(data.stats);
            updateTicketsTable(data.tickets);

            lastUpdated.textContent = `Обновлено: ${data.last_updated}`;
            statusIndicator.className = 'w-2 h-2 bg-green-500 rounded-full';

        } catch (error) {
            console.error('Ошибка при обновлении заявок:', error);
            statusIndicator.className = 'w-2 h-2 bg-red-500 rounded-full';
            lastUpdated.textContent = 'Ошибка обновления';

            // Redirect to login if unauthorized in production
            if (error.message.includes('403') || error.message.includes('401')) {
                if (window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
                    window.location.href = '/login';
                }
            }
        }
    }

    // Обновление статистики
    function updateStats(stats) {
        document.getElementById('total-count').textContent = stats.total;
        document.getElementById('open-count').textContent = stats.open;
        document.getElementById('progress-count').textContent = stats.in_progress;
        document.getElementById('resolved-count').textContent = stats.resolved;
        document.getElementById('closed-count').textContent = stats.closed;
    }

    // Обновление таблицы заявок
    function updateTicketsTable(tickets) {
        const tbody = document.getElementById('tickets-tbody');
        const emptyState = document.getElementById('empty-state');

        if (tickets.length === 0) {
            if (tbody) tbody.innerHTML = '';
            if (emptyState) emptyState.style.display = 'block';
            return;
        }

        if (emptyState) emptyState.style.display = 'none';

        if (tbody) {
            tbody.innerHTML = tickets.map(ticket => createTicketRow(ticket)).join('');
        }
    }

    // Создание строки таблицы
    function createTicketRow(ticket) {
        const statusColors = {
            'open': 'bg-blue-100 text-blue-800',
            'in_progress': 'bg-yellow-100 text-yellow-800',
            'resolved': 'bg-green-100 text-green-800',
            'closed': 'bg-slate-100 text-slate-800'
        };

        const statusLabels = {
            'open': 'Открыта',
            'in_progress': 'В работе',
            'resolved': 'Решена',
            'closed': 'Закрыта'
        };

        // Используем те же цвета приоритетов, что и в глобальных функциях
        const priorityColors = {
            'low': 'bg-green-100 text-green-800',
            'medium': 'bg-yellow-100 text-yellow-800',
            'high': 'bg-red-100 text-red-800',
            'urgent': 'bg-red-200 text-red-900'
        };

        const priorityLabels = {
            'low': 'Низкий',
            'medium': 'Средний',
            'high': 'Высокий',
            'urgent': 'Срочный'
        };

        const roomInfo = ticket.room ? `<div class="text-xs text-slate-500 mt-1">🏢 ${ticket.room.number} - ${ticket.room.name}</div>` :
                        (ticket.location_name ? `<div class="text-xs text-slate-500 mt-1">📍 ${ticket.location_name}</div>` : '');

        // Escape HTML in title and description for safety
        const safeTitle = ticket.title ? ticket.title.replace(/</g, '&lt;').replace(/>/g, '&gt;') : 'Без названия';
        const safeDescription = ticket.description ? ticket.description.replace(/</g, '&lt;').replace(/>/g, '&gt;') : '';

        return `
            <tr class="hover:bg-slate-50 transition-colors duration-200" data-ticket-id="${ticket.id}">
                <td class="w-10 px-3 py-4">
                    <div class="flex justify-center">
                        <input type="checkbox" class="ticket-checkbox rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" data-id="${ticket.id}">
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div>
                        <a href="${ticket.url}"
                           class="text-slate-900 font-medium hover:text-blue-600 transition-colors duration-200 break-words inline-block w-full">
                            <span class="line-clamp-2 ticket-title">${safeTitle}</span>
                        </a>
                        <p class="text-sm text-slate-600 mt-1 line-clamp-2 break-words">
                            ${safeDescription.substring(0, 80)}${safeDescription.length > 80 ? '...' : ''}
                        </p>
                        ${roomInfo}
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm">
                        <div class="font-medium text-slate-900">${ticket.reporter_name || '—'}</div>
                        <div class="text-slate-600">${ticket.reporter_phone || '—'}</div>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusColors[ticket.status] || 'bg-slate-100 text-slate-800'}" style="white-space: nowrap; min-width: 80px;">
                        ${statusLabels[ticket.status] || ticket.status}
                    </span>
                </td>
                <td class="px-6 py-4">
                    <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-medium ${ticket.priority === 'urgent' ? 'bg-red-200 text-red-900' : (priorityColors[ticket.priority] || 'bg-slate-100 text-slate-800')}" style="white-space: nowrap; min-width: 80px;">
                        ${ticket.priority === 'urgent' ? 'Срочный' : (priorityLabels[ticket.priority] || ticket.priority)}
                    </span>
                </td>
                <td class="px-6 py-4">
                    <span class="text-sm text-slate-600">
                        ${ticket.assigned_to || 'Не назначено'}
                    </span>
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm text-slate-600">
                        <div>${ticket.created_at}</div>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center gap-2">
                        <a href="${ticket.url}"
                           class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                            Просмотр
                        </a>
                        <div class="relative inline-block text-left ml-2" data-dropdown-id="${ticket.id}">
                            <button type="button" class="actions-menu-button p-1 rounded-full hover:bg-slate-200 focus:outline-none" aria-label="Действия" data-id="${ticket.id}">
                                <svg class="w-5 h-5 text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                </svg>
                            </button>
                            <div class="actions-menu hidden absolute right-0 mt-2 w-56 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none border border-gray-200 z-50" style="min-width: 8rem;" role="menu" aria-orientation="vertical" aria-labelledby="menu-button" tabindex="-1">
                                <div class="py-1" role="none">
                                    <a href="${ticket.url}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" role="menuitem">Просмотр заявки</a>
                                    ${ticket.status !== 'in_progress' ? `<button type="button" class="single-action block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" data-action="change-status" data-id="${ticket.id}" data-status="in_progress" role="menuitem">Взять в работу</button>` : ''}
                                    ${ticket.status !== 'resolved' ? `<button type="button" class="single-action block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" data-action="change-status" data-id="${ticket.id}" data-status="resolved" role="menuitem">Отметить как решенную</button>` : ''}
                                    ${ticket.status !== 'closed' ? `<button type="button" class="single-action block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" data-action="change-status" data-id="${ticket.id}" data-status="closed" role="menuitem">Закрыть заявку</button>` : ''}
                                    <button type="button" class="single-action block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" data-action="assign-to" data-id="${ticket.id}" role="menuitem">Назначить исполнителя</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        `;
    }

    // События
    if (refreshBtn) {
        refreshBtn.addEventListener('click', refreshTickets);
    }

    // Обработка клика на кнопке действий
    // Глобальный обработчик кликов для закрытия всех меню
    document.addEventListener('click', function(e) {
        // Закрытие всех открытых меню при клике вне них
        if (!e.target.closest('.actions-menu-button') && !e.target.closest('.actions-menu')) {
            document.querySelectorAll('.actions-menu').forEach(menu => {
                menu.classList.add('hidden');
            });
            document.querySelectorAll('.actions-menu-button').forEach(button => {
                button.classList.remove('bg-slate-200');
            });
        }

        // Открытие меню действий при клике на кнопку
        if (e.target.closest('.actions-menu-button')) {
            const button = e.target.closest('.actions-menu-button');
            const ticketId = button.getAttribute('data-id');
            const dropdownContainer = button.closest('[data-dropdown-id]');
            const dropdown = dropdownContainer.querySelector('.actions-menu');

            // Закрыть все другие открытые меню
            document.querySelectorAll('.actions-menu').forEach(menu => {
                if (menu !== dropdown) {
                    menu.classList.add('hidden');
                }
            });

            document.querySelectorAll('.actions-menu-button').forEach(btn => {
                if (btn !== button) {
                    btn.classList.remove('bg-slate-200');
                }
            });

            // Переключить текущее меню
            dropdown.classList.toggle('hidden');
            button.classList.toggle('bg-slate-200');

            // Корректное позиционирование меню
            const rect = button.getBoundingClientRect();
            const scrollTop = window.scrollY || document.documentElement.scrollTop;
            const scrollLeft = window.scrollX || document.documentElement.scrollLeft;

            // Устанавливаем позицию с учетом скролла
            dropdown.style.top = (rect.bottom + scrollTop) + 'px';
            dropdown.style.left = (rect.left + scrollLeft - dropdown.offsetWidth + rect.width) + 'px';

            e.preventDefault();
            e.stopPropagation();
        }

        // Обработка одиночных действий
        if (e.target.closest('.single-action')) {
            e.preventDefault();
            e.stopPropagation();
            const button = e.target.closest('.single-action');
            const action = button.getAttribute('data-action');
            const ticketId = button.getAttribute('data-id');
            const status = button.getAttribute('data-status');

            console.log('Action clicked:', action, 'ticket:', ticketId, 'status:', status);

            if (action === 'change-status' && status) {
                changeTicketStatus(ticketId, status);
            } else if (action === 'assign-to') {
                assignTicket(ticketId);
            }

            // Закрыть меню
            const dropdown = button.closest('.actions-menu');
            if (dropdown) {
                dropdown.classList.add('hidden');
            }
        }
    });

    // Обработка чекбоксов и массовых действий
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    const bulkActionsBar = document.getElementById('bulk-actions-bar');
    const selectedCountSpan = document.getElementById('selected-count');
    const bulkActionsButton = document.getElementById('bulk-actions-button');
    const bulkActionsMenu = document.getElementById('bulk-actions-menu');
    const bulkCancelButton = document.getElementById('bulk-cancel-button');

    // Переключение всех чекбоксов
    selectAllCheckbox.addEventListener('change', function() {
        const isChecked = this.checked;
        document.querySelectorAll('.ticket-checkbox').forEach(checkbox => {
            checkbox.checked = isChecked;
        });
        updateBulkActionsBar();
    });

    // Обновление панели массовых действий при изменении отдельных чекбоксов
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('ticket-checkbox')) {
            updateBulkActionsBar();
        }
    });

    // Обновление отображения панели массовых действий
    function updateBulkActionsBar() {
        const checkedCheckboxes = document.querySelectorAll('.ticket-checkbox:checked');
        const selectedCount = checkedCheckboxes.length;

        if (selectedCount > 0) {
            bulkActionsBar.classList.remove('hidden');
            bulkActionsBar.classList.add('flex');
            selectedCountSpan.textContent = selectedCount;
        } else {
            bulkActionsBar.classList.add('hidden');
            bulkActionsBar.classList.remove('flex');
            selectAllCheckbox.checked = false;
        }
    }

    // Открытие/закрытие выпадающего меню массовых действий
    if (bulkActionsButton) {
        bulkActionsButton.addEventListener('click', function(e) {
            bulkActionsMenu.classList.toggle('hidden');
            e.preventDefault();
            e.stopPropagation();
        });
    }

    // Закрытие выпадающего меню при клике вне его
    document.addEventListener('click', function(e) {
        if (bulkActionsButton && bulkActionsMenu && !bulkActionsButton.contains(e.target) && !bulkActionsMenu.contains(e.target)) {
            bulkActionsMenu.classList.add('hidden');
        }
    });

    // Отмена выбора
    if (bulkCancelButton) {
        bulkCancelButton.addEventListener('click', function() {
            document.querySelectorAll('.ticket-checkbox').forEach(checkbox => {
                checkbox.checked = false;
            });
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = false;
            }
            updateBulkActionsBar();
        });
    }

    // Обработка массовых действий
    document.querySelectorAll('.bulk-action-item').forEach(button => {
        button.addEventListener('click', function() {
            const action = this.getAttribute('data-action');
            const status = this.getAttribute('data-status');
            const selectedTickets = Array.from(document.querySelectorAll('.ticket-checkbox:checked')).map(cb => cb.getAttribute('data-id'));

            if (selectedTickets.length === 0) return;

            if (action === 'change-status' && status) {
                // Изменение статуса для всех выбранных заявок
                Promise.all(selectedTickets.map(id => changeTicketStatus(id, status)))
                    .then(() => {
                        // После обработки всех заявок
                        refreshTickets();
                        bulkActionsMenu.classList.add('hidden');
                        updateBulkActionsBar();
                    });
            } else if (action === 'assign-to') {
                // Назначение исполнителя для всех выбранных заявок
                assignMultipleTickets(selectedTickets);
                bulkActionsMenu.classList.add('hidden');
                updateBulkActionsBar();
            }
        });
    });

    // Функция изменения статуса заявки
    function changeTicketStatus(ticketId, status) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Закрываем все открытые меню перед отправкой запроса
        document.querySelectorAll('.actions-menu').forEach(menu => {
            menu.classList.add('hidden');
        });

        document.querySelectorAll('.actions-menu-button').forEach(button => {
            button.classList.remove('bg-slate-200');
        });

        return fetch(`{{ route('api.tickets.status', ['ticket' => 0]) }}`.replace('0', ticketId), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ status: status })
        })
        .then(response => {
            if (response.ok) {
                showNotification(`Статус заявки #${ticketId} изменен на "${getStatusLabel(status)}"`, 'success');
                refreshTickets();
                return response.json();
            }
            throw new Error('Ошибка при изменении статуса');
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Произошла ошибка при изменении статуса заявки', 'error');
        });
    }

    // Функция назначения исполнителя для заявки
    function assignTicket(ticketId) {
        // Создаем модальное окно для выбора исполнителя
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.id = 'assign-modal';

        // Получаем список доступных исполнителей
        fetch('{{ route('api.users.technicians') }}')
            .then(response => response.json())
            .then(data => {
                const technicians = data.technicians || [];

                modal.innerHTML = `
                    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-auto">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Назначение исполнителя</h3>
                            <button type="button" class="text-gray-400 hover:text-gray-500" id="close-modal">
                                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div class="mb-6">
                            <label for="technician-select" class="block text-sm font-medium text-gray-700 mb-2">Выберите исполнителя</label>
                            <select id="technician-select" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Не назначен</option>
                                ${technicians.map(tech => `<option value="${tech.id}">${tech.name}</option>`).join('')}
                            </select>
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300" id="cancel-assign">
                                Отмена
                            </button>
                            <button type="button" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700" id="confirm-assign">
                                Назначить
                            </button>
                        </div>
                    </div>
                `;

                document.body.appendChild(modal);

                // Обработчики событий для модального окна
                document.getElementById('close-modal').addEventListener('click', () => {
                    document.body.removeChild(modal);
                });

                document.getElementById('cancel-assign').addEventListener('click', () => {
                    document.body.removeChild(modal);
                });

                document.getElementById('confirm-assign').addEventListener('click', () => {
                    const technicianId = document.getElementById('technician-select').value;
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                    fetch(`{{ route('api.tickets.assign', ['ticket' => 0]) }}`.replace('0', ticketId), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            assigned_to_id: technicianId || null
                        })
                    })
                    .then(response => {
                        if (response.ok) {
                            return response.json();
                        }
                        throw new Error('Ошибка при назначении исполнителя');
                    })
                    .then(data => {
                        const assignedName = data.assigned_to || 'Не назначен';
                        showNotification(`Исполнитель заявки #${ticketId} изменен на "${assignedName}"`, 'success');
                        refreshTickets();
                        document.body.removeChild(modal);
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Произошла ошибка при назначении исполнителя', 'error');
                        document.body.removeChild(modal);
                    });
                });
            })
            .catch(error => {
                console.error('Error loading technicians:', error);
                showNotification('Не удалось загрузить список исполнителей', 'error');
            });
    }

    // Функция назначения исполнителя для нескольких заявок
    function assignMultipleTickets(ticketIds) {
        // Создаем модальное окно для выбора исполнителя
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.id = 'assign-multi-modal';

        // Получаем список доступных исполнителей
        fetch('{{ route('api.users.technicians') }}')
            .then(response => response.json())
            .then(data => {
                const technicians = data.technicians || [];

                modal.innerHTML = `
                    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-auto">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Назначение исполнителя для ${ticketIds.length} заявок</h3>
                            <button type="button" class="text-gray-400 hover:text-gray-500" id="close-multi-modal">
                                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div class="mb-6">
                            <label for="multi-technician-select" class="block text-sm font-medium text-gray-700 mb-2">Выберите исполнителя</label>
                            <select id="multi-technician-select" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Не назначен</option>
                                ${technicians.map(tech => `<option value="${tech.id}">${tech.name}</option>`).join('')}
                            </select>
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300" id="cancel-multi-assign">
                                Отмена
                            </button>
                            <button type="button" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700" id="confirm-multi-assign">
                                Назначить
                            </button>
                        </div>
                    </div>
                `;

                document.body.appendChild(modal);

                // Обработчики событий для модального окна
                document.getElementById('close-multi-modal').addEventListener('click', () => {
                    document.body.removeChild(modal);
                });

                document.getElementById('cancel-multi-assign').addEventListener('click', () => {
                    document.body.removeChild(modal);
                });

                document.getElementById('confirm-multi-assign').addEventListener('click', () => {
                    const technicianId = document.getElementById('multi-technician-select').value;
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                    // Отображаем индикатор прогресса
                    showNotification(`Назначение исполнителя для ${ticketIds.length} заявок...`, 'info');

                    // Создаем массив промисов для каждой заявки
                    const promises = ticketIds.map(ticketId =>
                        fetch(`{{ route('api.tickets.assign', ['ticket' => 0]) }}`.replace('0', ticketId), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                assigned_to_id: technicianId || null
                            })
                        })
                    );

                    // Обрабатываем все запросы
                    Promise.all(promises)
                        .then(responses => {
                            // Проверяем, что все запросы успешны
                            const allSuccessful = responses.every(response => response.ok);
                            if (allSuccessful) {
                                const techName = technicians.find(t => t.id.toString() === technicianId)?.name || 'Не назначен';
                                showNotification(`Исполнитель для ${ticketIds.length} заявок изменен на "${techName}"`, 'success');
                                refreshTickets();
                            } else {
                                showNotification('Произошли ошибки при назначении некоторых заявок', 'warning');
                            }
                            document.body.removeChild(modal);
                            updateBulkActionsBar(); // Сбрасываем выделение заявок
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showNotification('Произошла ошибка при назначении исполнителей', 'error');
                            document.body.removeChild(modal);
                        });
                });
            })
            .catch(error => {
                console.error('Error loading technicians:', error);
                showNotification('Не удалось загрузить список исполнителей', 'error');
            });
    }

    // Функция получения текстового представления статуса
    function getStatusLabel(status) {
        const statusLabels = {
            'open': 'Открыта',
            'in_progress': 'В работе',
            'resolved': 'Решена',
            'closed': 'Закрыта'
        };
        return statusLabels[status] || status;
    }

    // Функция для отображения уведомлений
    function showNotification(message, type = 'info') {
        const notificationElement = document.createElement('div');
        notificationElement.classList.add('fixed', 'bottom-4', 'right-4', 'px-6', 'py-3', 'rounded-lg', 'shadow-lg', 'z-50', 'transform', 'transition-all', 'duration-500', 'translate-y-20', 'opacity-0');

        // Добавляем цвета в зависимости от типа уведомления
        if (type === 'success') {
            notificationElement.classList.add('bg-green-600', 'text-white');
        } else if (type === 'error') {
            notificationElement.classList.add('bg-red-600', 'text-white');
        } else if (type === 'info') {
            notificationElement.classList.add('bg-blue-600', 'text-white');
        } else if (type === 'warning') {
            notificationElement.classList.add('bg-yellow-500', 'text-white');
        }

        notificationElement.innerHTML = message;
        document.body.appendChild(notificationElement);

        // Анимируем появление
        setTimeout(() => {
            notificationElement.classList.remove('translate-y-20', 'opacity-0');
        }, 100);

        // Удаляем через 3 секунды
        setTimeout(() => {
            notificationElement.classList.add('translate-y-20', 'opacity-0');
            setTimeout(() => {
                document.body.removeChild(notificationElement);
            }, 500);
        }, 3000);
    }

    // Автоматическое применение фильтров
    const filtersForm = document.getElementById('filters-form');
    if (filtersForm) {
        const selectInputs = filtersForm.querySelectorAll('select');
        selectInputs.forEach(input => {
            input.addEventListener('change', function() {
                stopAutoRefresh();
                filtersForm.submit();
            });
        });
    }

    // Очистка фильтров
    const clearFiltersBtn = document.getElementById('clear-filters');
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function() {
            if (filtersForm) {
                filtersForm.reset();
                window.location.href = window.location.pathname;
            }
        });
    }

    // Поиск с задержкой
    let searchTimeout;
    document.getElementById('search').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            stopAutoRefresh();
            refreshTickets().then(startAutoRefresh);
        }, 500);
    });

    // Запуск автообновления
    startAutoRefresh();

    // Остановка автообновления при уходе со страницы
    window.addEventListener('beforeunload', stopAutoRefresh);

    // Начальная загрузка времени
    lastUpdated.textContent = `Загружено: ${new Date().toLocaleString('ru-RU')}`;
});

// Инициализация UI элементов
// Переключение видимости фильтров на мобильных устройствах
document.addEventListener('DOMContentLoaded', function() {
    // Переключение видимости фильтров на мобильных устройствах
    const toggleFiltersBtn = document.getElementById('toggle-filters');
    const filtersContainer = document.querySelector('.filters-container');
    const searchInput = document.getElementById('search');

    if (searchInput) {
        // Фокус на поле поиска при загрузке страницы
        setTimeout(() => {
            searchInput.focus();
        }, 100);
    }

    if (toggleFiltersBtn && filtersContainer) {
        // Скрыть фильтры по умолчанию на мобильных
        if (window.innerWidth < 768) {
            filtersContainer.classList.add('hidden');
        }

        toggleFiltersBtn.addEventListener('click', function() {
            const showText = toggleFiltersBtn.querySelector('.show-text');
            const hideText = toggleFiltersBtn.querySelector('.hide-text');

            filtersContainer.classList.toggle('hidden');
            if (showText && hideText) {
                showText.classList.toggle('hidden');
                hideText.classList.toggle('hidden');
            }
        });
    }
});
</script>
@endpush
@endsection
