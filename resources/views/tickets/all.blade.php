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
                            {{ $location->name ?? 'Без названия' }}
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
                            {{ $room->number ?? 'б/н' }} - {{ $room->name ?? ($room->type_name ?? 'Без названия') }}
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
                            {{ $user->name ?? 'Пользователь #'.$user->id }}
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

        <style>
            /* Стили для предотвращения горизонтального скролла */
            #tickets-container,
            #tickets-container > div,
            #tickets-container table {
                width: 100%;
            }
            #tickets-container .overflow-x-auto,
            #tickets-container .overflow-auto {
                overflow-x: auto !important;
            }
            #tickets-container table {
                width: 100% !important;
                table-layout: fixed !important;
                border-collapse: separate;
                border-spacing: 0;
            }
            #tickets-container td {
                word-wrap: break-word;
                overflow-wrap: break-word;
                padding: 12px !important;
                vertical-align: top;
                line-height: 1.4;
                max-width: 100%;
                position: relative;
            }
            #tickets-container tr {
                transition: all 0.2s ease;
                border-bottom: 1px solid #f1f5f9;
            }
            #tickets-container tr:hover {
                background-color: #f8fafc;
            }
            #tickets-container tbody tr:last-child {
                border-bottom: none;
            }
            #tickets-container .line-clamp-2 {
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
            #tickets-container .line-clamp-3 {
                display: -webkit-box;
                -webkit-line-clamp: 3;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
            #tickets-container .ticket-title {
                font-size: 17px;
                font-weight: 600;
                color: #1e293b;
                line-height: 1.5;
                margin-bottom: 8px;
                display: block;
                text-decoration: none;
            }
            #tickets-container .ticket-title:hover {
                color: #2563eb;
                text-decoration: underline;
            }
            #tickets-container .ticket-description {
                color: #64748b;
                font-size: 14px;
                line-height: 1.6;
                margin-bottom: 12px;
                max-width: 100%;
                overflow: hidden;
                display: -webkit-box;
                -webkit-box-orient: vertical;
                -webkit-line-clamp: 2;
                min-height: 2em;
                word-break: break-word;
            }
            #tickets-container .ticket-meta {
                display: flex;
                gap: 8px;
                margin-top: 12px;
                flex-wrap: wrap;
            }
            #tickets-container .ticket-meta-item {
                display: inline-flex;
                align-items: center;
                font-size: 12px;
                color: #64748b;
                background-color: #f1f5f9;
                padding: 4px 8px;
                border-radius: 4px;
                white-space: nowrap;
                max-width: 100%;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            #tickets-container .ticket-meta-item svg {
                flex-shrink: 0;
                margin-right: 4px;
            }
            .refresh-button {
                background-color: #f8fafc;
                color: #475569;
                border: 1px solid #e2e8f0;
                border-radius: 0.375rem;
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
                font-weight: 500;
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                cursor: pointer;
                transition: all 0.2s;
            }
            .refresh-button:hover {
                background-color: #f1f5f9;
                border-color: #cbd5e1;
            }
            .refresh-button:active {
                background-color: #e2e8f0;
            }
            .refresh-button svg {
                width: 1rem;
                height: 1rem;
            }
            .refresh-button.refreshing svg {
                animation: spin 1s linear infinite;
            }
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
        </style>

        <div class="flex flex-col md:flex-row justify-between items-start md:items-center p-5 mt-6 mb-4 gap-4">
            <h2 class="text-xl font-semibold text-slate-900">Заявки в системе <span class="text-slate-500 text-sm font-normal ml-2">{{ $tickets->total() }} записей</span></h2>
        </div>
        <div id="tickets-container">
            @if($tickets->count() > 0)
                <div class="card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th class="px-4 py-4 text-left text-sm font-semibold text-slate-900">Заявка</th>
                                    <th class="px-4 py-4 text-left text-sm font-semibold text-slate-900">Заявитель</th>
                                    <th class="px-4 py-4 text-left text-sm font-semibold text-slate-900">Статус</th>
                                    <th class="px-4 py-4 text-left text-sm font-semibold text-slate-900">Приоритет</th>
                                    <th class="px-4 py-4 text-left text-sm font-semibold text-slate-900">Исполнитель</th>
                                    <th class="px-4 py-4 text-center text-sm font-semibold text-slate-900">Действия</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200" id="tickets-tbody">
                                <!-- Содержимое будет заполнено через SmartUpdates -->
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="text-center py-20 bg-white rounded-lg border border-slate-200 shadow-sm" id="empty-state">
                    <div class="mx-auto w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-10 h-10 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-medium text-slate-900 mb-2">Заявок не найдено</h3>
                    <p class="text-slate-600 mb-8 max-w-md mx-auto">Попробуйте изменить параметры фильтра или создайте новую заявку</p>
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
<script src="{{ Vite::asset('resources/js/websocket-client.js') }}"></script>
<script src="{{ Vite::asset('resources/js/live-updates.js') }}"></script>
<script src="{{ Vite::asset('resources/js/smart-updates.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let liveUpdates;
    let refreshInterval;
    const REFRESH_INTERVAL = 1000; // 1 секунда

    // Инициализируем таблицу с начальными данными
    const initialTicketsData = @json($tickets);
    console.log('Начальные данные заявок:', initialTicketsData);
    
    // Извлекаем массив заявок из объекта пагинации
    const initialTickets = initialTicketsData && initialTicketsData.data ? initialTicketsData.data : [];
    console.log('Массив заявок:', initialTickets);
    console.log('Количество заявок:', initialTickets.length);
    
    if (initialTickets && initialTickets.length > 0) {
        console.log('Загружаем заявки в таблицу...');
        updateTicketsTable(initialTickets);
        // Инициализируем обработчики для начальных данных
        setTimeout(() => {
            initTableDropdowns();
        }, 100);
    } else {
        console.log('Нет заявок для отображения');
    }

    // Элементы
    const refreshBtn = document.getElementById('refresh-btn');
    const statusIndicator = document.getElementById('status-indicator');
    const lastUpdated = document.getElementById('last-updated');
    const loadingIndicator = document.getElementById('loading-indicator');
    const ticketsContainer = document.getElementById('tickets-container');
    const filtersForm = document.getElementById('filters-form');
    const clearFiltersBtn = document.getElementById('clear-filters');

    // Проверяем наличие необходимых элементов
    if (!filtersForm) {
        console.error('Форма фильтров не найдена');
        return;
    }

    // Инициализация LiveUpdates
    function initLiveUpdates() {
        console.log('Проверяем доступность LiveUpdates:', typeof LiveUpdates);
        
        if (typeof LiveUpdates === 'undefined') {
            console.error('LiveUpdates не загружен, используем fallback');
            // Fallback к старому методу
            startAutoRefresh();
            return;
        }
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        
        // Временно отключаем LiveUpdates для отладки
        console.log('LiveUpdates отключен для отладки');
        
        // Вместо этого используем fallback
        setTimeout(() => {
            const initialTickets = @json($tickets);
            if (initialTickets && initialTickets.length > 0) {
                updateTicketsTable(initialTickets);
            }
        }, 100);
    }
    
    // Fallback функции для старого метода
    function startAutoRefresh() {
        refreshInterval = setInterval(refreshTickets, REFRESH_INTERVAL);
        console.log('Fallback: Авто-обновление запущено с интервалом', REFRESH_INTERVAL, 'мс');
    }

    // Обновление заявок
    async function refreshTickets() {
        try {
            if (statusIndicator) {
                statusIndicator.className = 'w-2 h-2 bg-green-500 rounded-full';
            }

            const formData = new FormData(filtersForm);
            const params = new URLSearchParams(formData);

            const response = await fetch(`{{ route('all-tickets.api') }}?${params}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                cache: 'no-store',
                credentials: 'same-origin'
            });

            if (!response.ok) {
                if (response.status === 401 || response.status === 403) {
                    console.warn('Ошибка аутентификации, перенаправляем на логин');
                    window.location.href = '/login';
                    return;
                }
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            updateStats(data.stats);
            updateTicketsTable(data.tickets);

            if (lastUpdated) lastUpdated.textContent = `Обновлено: ${data.last_updated}`;
            if (statusIndicator) statusIndicator.className = 'w-2 h-2 bg-green-500 rounded-full';

        } catch (error) {
            console.error('Ошибка при обновлении заявок:', error);
            if (statusIndicator) {
                statusIndicator.className = 'w-2 h-2 bg-red-500 rounded-full';
                // Возвращаем зеленый цвет через 30 секунд
                setTimeout(() => {
                    if (statusIndicator) {
                        statusIndicator.className = 'w-2 h-2 bg-green-500 rounded-full';
                    }
                }, 30000);
            }
            if (lastUpdated) lastUpdated.textContent = 'Ошибка обновления';

            // Обработка ошибок аутентификации
            if (error.message.includes('401') || error.message.includes('403') || error.message.includes('Unauthorized')) {
                console.warn('Ошибка аутентификации, перенаправляем на логин');
                setTimeout(() => {
                    window.location.href = '/login';
                }, 1000);
            }
        }
    }

    // Обновление статистики
    function updateStats(stats) {
        const totalEl = document.getElementById('total-count');
        const openEl = document.getElementById('open-count');
        const progressEl = document.getElementById('progress-count');
        const resolvedEl = document.getElementById('resolved-count');
        const closedEl = document.getElementById('closed-count');

        if (totalEl) totalEl.textContent = stats.total;
        if (openEl) openEl.textContent = stats.open;
        if (progressEl) progressEl.textContent = stats.in_progress;
        if (resolvedEl) resolvedEl.textContent = stats.resolved;
        if (closedEl) closedEl.textContent = stats.closed;
    }

    // Умная система обновления заявок
    let smartUpdates;

    // Обновление таблицы заявок с умным механизмом
    function updateTicketsTable(tickets) {
        console.log('updateTicketsTable вызвана с заявками:', tickets);
        const tbody = document.getElementById('tickets-tbody');
        const emptyState = document.getElementById('empty-state');
        
        console.log('Найден tbody:', tbody);
        console.log('Найден emptyState:', emptyState);

        if (tickets.length === 0) {
            console.log('Нет заявок, показываем пустое состояние');
            if (tbody) tbody.innerHTML = '';
            if (emptyState) emptyState.style.display = 'block';
            if (smartUpdates) smartUpdates.clear();
            return;
        }

        if (emptyState) emptyState.style.display = 'none';

        if (tbody) {
            // Используем простой fallback без SmartUpdates
            console.log('Используем простой fallback для отображения таблицы');
            const html = tickets.map(ticket => createTicketRow(ticket)).join('');
            console.log('Сгенерированный HTML:', html.substring(0, 200) + '...');
            tbody.innerHTML = html;
            setTimeout(() => {
                initTableDropdowns();
            }, 100);
        } else {
            console.error('tbody не найден!');
        }
    }


    // Создание строки таблицы
    function createTicketRow(ticket) {
        console.log('Создаем строку для заявки:', ticket);
        console.log('ID заявки:', ticket.id);
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

        // Генерируем URL для заявки
        const ticketUrl = `/tickets/${ticket.id}`;
        console.log('URL заявки:', ticketUrl);
        
        // Упрощенное отображение информации о помещении для экономии места
        const roomInfo = ticket.room ? `<div class="text-xs text-slate-500 mt-1">🏢 ${ticket.room.number}</div>` :
                        (ticket.location_name ? `<div class="text-xs text-slate-500 mt-1">📍 ${ticket.location_name}</div>` : '');
        
        // Escape HTML in title and description for safety
        const safeTitle = ticket.title ? ticket.title.replace(/</g, '&lt;').replace(/>/g, '&gt;') : 'Без названия';
        const safeDescription = ticket.description ? ticket.description.replace(/</g, '&lt;').replace(/>/g, '&gt;') : '';

        return `
            <tr class="hover:bg-slate-50 transition-all duration-300" data-ticket-id="${ticket.id}">
                <td class="px-4 py-3">
                    <div>
                        <a href="${ticketUrl}"
                           class="ticket-title line-clamp-2 break-words inline-block transition-all duration-300"
                           title="${safeTitle}">
                            ${ticket.id ? `#${ticket.id}: ` : ''}${safeTitle}
                        </a>
                        <p class="ticket-description line-clamp-3 break-words"
                           title="${safeDescription || 'Описание отсутствует'}">
                            ${safeDescription || 'Описание отсутствует'}
                        </p>
                        <div class="ticket-meta">
                            ${ticket.category ? `
                            <span class="ticket-meta-item">
                                <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                                ${formatTicketCategory(ticket.category)}
                            </span>` : ''}
                            ${roomInfo ? `
                            <span class="ticket-meta-item">
                                <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd" />
                                </svg>
                                ${roomInfo.replace(/🏢 |📍 /g, '')}
                            </span>` : ''}
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3">
                    <div class="text-sm">
                        <div class="font-medium text-slate-900 truncate" title="${ticket.reporter_name || '—'}">${ticket.reporter_name || '—'}</div>
                        <div class="text-slate-600 truncate" title="${ticket.reporter_phone || '—'}">${ticket.reporter_phone ? formatPhone(ticket.reporter_phone) : '—'}</div>
                    </div>
                </td>
                <td class="px-4 py-3">
                    <span class="inline-flex items-center justify-center px-2 py-1 rounded-full text-xs font-medium ${getStatusClass(ticket.status)}" 
                          style="min-width: 80px; text-align: center; white-space: nowrap;"
                          title="Статус: ${getStatusLabel(ticket.status)}">
                        ${getStatusLabel(ticket.status)}
                    </span>
                </td>
                <td class="px-4 py-3">
                    <span class="inline-flex items-center justify-center px-2 py-1 rounded-full text-xs font-medium ${priorityColors[ticket.priority]}" 
                          style="min-width: 80px; text-align: center;"
                          title="Приоритет: ${priorityLabels[ticket.priority]}">
                        ${priorityLabels[ticket.priority]}
                    </span>
                </td>
                <td class="px-4 py-3">
                    ${ticket.assigned_to_name ? `
                        <div class="text-sm">
                            <div class="font-medium text-slate-900 truncate" title="${ticket.assigned_to_name}">${ticket.assigned_to_name}</div>
                            ${ticket.assigned_to_role ? `<div class="text-xs text-slate-500 truncate" title="${ticket.assigned_to_role}">${ticket.assigned_to_role}</div>` : ''}
                        </div>
                    ` : '<span class="text-sm text-slate-500 italic">Не назначено</span>'}
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                        <div class="flex items-center justify-end">
                            <div class="relative inline-block">
                                <button type="button" class="actions-btn text-slate-500 hover:text-slate-700 p-2 transition-all duration-300 rounded-full hover:bg-slate-100" data-ticket-id="${ticket.id}" title="Действия">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
                                    </svg>
                                </button>
                                <div class="actions-menu absolute right-0 mt-2 w-48 bg-white rounded-md shadow-xl border border-slate-200 z-50 hidden" data-ticket-id="${ticket.id}">
                                    <div class="py-1">
                                        <a href="${ticketUrl}" class="block px-4 py-3 text-sm text-slate-700 hover:bg-slate-100 transition">Просмотр заявки</a>
                                        ${ticket.status !== 'in_progress' && ticket.status !== 'closed' && !ticket.assigned_to_name ? `<button type="button" class="block w-full text-left px-4 py-3 text-sm text-slate-700 hover:bg-slate-100 transition single-action" data-action="change-status" data-id="${ticket.id}" data-status="in_progress">Взять в работу</button>` : ''}
                                        ${ticket.status === 'in_progress' && ticket.assigned_to_name ? `<button type="button" class="block w-full text-left px-4 py-3 text-sm text-slate-700 hover:bg-slate-100 transition single-action" data-action="change-status" data-id="${ticket.id}" data-status="resolved">Отметить решённой</button>` : ''}
                                        ${ticket.status === 'resolved' && ticket.assigned_to_name ? `<button type="button" class="block w-full text-left px-4 py-3 text-sm text-slate-700 hover:bg-slate-100 transition single-action" data-action="change-status" data-id="${ticket.id}" data-status="closed">Закрыть заявку</button>` : ''}
                                        ${ticket.status !== 'closed' ? `<button type="button" class="block w-full text-left px-4 py-3 text-sm text-slate-700 hover:bg-slate-100 transition single-action" data-action="assign-to" data-id="${ticket.id}">Назначить исполнителя</button>` : ''}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
            </tr>
        `;
    }

    // События
    const refreshButton = document.getElementById('refresh-button');
    if (refreshButton) {
        refreshButton.addEventListener('click', function() {
            // Добавляем класс для анимации
            this.classList.add('refreshing');
            // Изменяем текст кнопки
            this.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                </svg>
                Обновление...
            `;
            // Запускаем обновление
            refreshTickets().then(() => {
                // После завершения возвращаем исходный вид
                setTimeout(() => {
                    this.classList.remove('refreshing');
                    this.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                        </svg>
                        Обновить данные
                    `;
                    // Показываем уведомление об успешном обновлении
                    showNotification('Данные успешно обновлены', 'success', 2000);
                }, 500);
            });
        });
    }

    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            if (liveUpdates) {
                liveUpdates.refresh();
            }
        });
    }

    // Обработка клика на кнопке действий
    // Глобальный обработчик кликов для закрытия всех меню
    document.addEventListener('click', function(e) {
        // Закрытие всех открытых меню при клике вне них
        if (!e.target.closest('.actions-btn') && !e.target.closest('.actions-menu')) {
            document.querySelectorAll('.actions-menu').forEach(menu => {
                menu.classList.add('hidden');
            });
            document.querySelectorAll('.actions-btn').forEach(button => {
                button.classList.remove('bg-slate-100');
            });
        }

        // Обработка одиночных действий
        if (e.target.closest('.single-action')) {
            e.preventDefault();
            e.stopPropagation();
            const button = e.target.closest('.single-action');
            const action = button.getAttribute('data-action');
            const ticketId = button.getAttribute('data-id');
            const status = button.getAttribute('data-status');

            // Визуальный эффект нажатия и текст кнопки
            button.classList.add('bg-gray-100');
            const buttonText = button.textContent.trim();

            // Показываем мини-уведомление внутри кнопки
            const originalText = button.innerHTML;
            button.innerHTML = '<span class="flex items-center justify-center gap-2"><svg class="animate-spin h-4 w-4 text-gray-800" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Выполняется...</span>';

            // Закрыть меню немедленно, чтобы пользователь видел, что клик обработан
            const ticketIdForMenu = button.getAttribute('data-id');
            const menu = document.querySelector(`.actions-menu[data-ticket-id="${ticketIdForMenu}"]`);
            if (menu) {
                menu.classList.add('hidden');
            }

            // Находим и убираем подсветку с кнопки меню
            const actionBtn = document.querySelector(`.actions-btn[data-ticket-id="${ticketIdForMenu}"]`);
            if (actionBtn) {
                actionBtn.classList.remove('bg-slate-100');
            }

            // Восстанавливаем текст кнопки через небольшую задержку
            setTimeout(() => {
                button.innerHTML = originalText;
            }, 500);

            // Сразу выполняем действие без задержки для лучшего отклика
            button.classList.remove('bg-gray-100');

            if (action === 'change-status' && status) {
                changeTicketStatus(ticketId, status);
            } else if (action === 'assign-to') {
                assignTicket(ticketId);
            }
        }
    });

    // Массовые действия полностью отключены

    // Обновление отображения панели массовых действий
    // Функция обновления панели массовых действий (отключена)
    function updateBulkActionsBar() {
        // Функционал отключен
        return;
    }

    // Открытие/закрытие выпадающего меню массовых действий
    // Функционал кнопок массовых действий отключен

    // Закрытие выпадающего меню при клике вне его
    // Меню массовых действий отключено

    // Отмена выбора
    // Функционал массовых действий отключен

    // Обработка массовых действий отключена
    /*
    document.querySelectorAll('.bulk-action-item').forEach(button => {
        button.addEventListener('click', function() {
            const action = this.getAttribute('data-action');
            const status = this.getAttribute('data-status');
            const selectedTickets = [];

            if (selectedTickets.length === 0) return;
    */
/*
console.log('Action clicked:', action, 'Tickets:', selectedTickets);

if (action === 'change-status' && status) {
    // Изменение статуса для всех выбранных заявок
    Promise.all(
        selectedTickets.map(ticketId => changeTicketStatus(ticketId, status))
    )
        .then(() => {
            // После обработки всех заявок
            refreshTickets();
        });
} else if (action === 'assign-to') {
    // Назначение исполнителя для всех выбранных заявок
    assignMultipleTickets(selectedTickets);
}
*/
// });

    // Функция изменения статуса заявки
    function changeTicketStatus(ticketId, status) {
        // Показываем индикатор загрузки с запоминанием заявки, на которой выполняется действие
        window.currentActionTicketId = ticketId;
        showNotification(`<span class="font-medium">Заявка #${ticketId}:</span> изменение статуса...`, 'info', 2000);

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
            if (!response.ok) {
                throw new Error('Ошибка сервера: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            // Уведомляем только если не было другого действия
            if (window.currentActionTicketId === ticketId) {
                showNotification(`<span class="font-medium">Заявка #${ticketId}:</span> статус изменен на <span class="font-medium">"${getStatusLabel(status)}"</span>`, 'success');
            }
            refreshTickets();
            return data;
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification(`<span class="font-medium">Ошибка:</span> не удалось изменить статус заявки #${ticketId}`, 'error', 5000);
        });
    }

    // Функция назначения исполнителя для заявки
    function assignTicket(ticketId) {
        // Показываем индикатор загрузки
        window.currentActionTicketId = ticketId;
        showNotification(`<span class="font-medium">Заявка #${ticketId}:</span> загрузка списка исполнителей...`, 'info', 2000);

        // Создаем модальное окно для выбора исполнителя
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[100]';
        modal.id = 'assign-modal';

        // Получаем список доступных исполнителей
        fetch('{{ route("api.users.technicians") }}')
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
                            <button type="button" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 transition" id="cancel-assign">
                                Отмена
                            </button>
                            <button type="button" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition" id="confirm-assign">
                                Назначить
                            </button>
                        </div>
                    </div>
                `;

                document.body.appendChild(modal);

                // Обработчики событий для модального окна
                document.getElementById('close-modal').addEventListener('click', (e) => {
                    e.preventDefault();
                    document.body.removeChild(modal);
                });

                document.getElementById('cancel-assign').addEventListener('click', (e) => {
                    e.preventDefault();
                    document.body.removeChild(modal);
                });

                document.getElementById('confirm-assign').addEventListener('click', (e) => {
                    e.preventDefault();
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
                        // Уведомляем только если не было другого действия
                        if (window.currentActionTicketId === ticketId) {
                            showNotification(`<span class="font-medium">Заявка #${ticketId}:</span> исполнитель изменен на <span class="font-medium">"${assignedName}"</span>`, 'success');
                        }
                        refreshTickets();
                        document.body.removeChild(modal);
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification(`<span class="font-medium">Ошибка:</span> не удалось назначить исполнителя для заявки #${ticketId}`, 'error', 5000);
                        document.body.removeChild(modal);
                    });
                });
            })
            .catch(error => {
                console.error('Error loading technicians:', error);
                showNotification(`<span class="font-medium">Ошибка:</span> не удалось загрузить список исполнителей для заявки #${ticketId}`, 'error', 5000);
            });
    }

    // Функция назначения исполнителя для нескольких заявок (отключена)
    function assignMultipleTickets(ticketIds) {
        // Полностью отключена
        return;
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

    // Функция форматирования даты
    function formatDate(dateString) {
        if (!dateString) return '—';
        const date = new Date(dateString);

        // Получаем текущую дату
        const today = new Date();
        const yesterday = new Date(today);
        yesterday.setDate(yesterday.getDate() - 1);

        // Проверяем, сегодня ли создана заявка
        if (date.toDateString() === today.toDateString()) {
            return 'Сегодня, ' + date.toLocaleTimeString('ru-RU', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // Проверяем, вчера ли создана заявка
        if (date.toDateString() === yesterday.toDateString()) {
            return 'Вчера, ' + date.toLocaleTimeString('ru-RU', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // Иначе возвращаем полную дату
        const options = {
            day: '2-digit',
            month: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        };

        // Если год не текущий, добавляем его в формат
        if (date.getFullYear() !== new Date().getFullYear()) {
            options.year = '2-digit';
        }

        return date.toLocaleDateString('ru-RU', options).replace(',', '');
    }

    // Функция форматирования телефона
    function formatPhone(phone) {
        if (!phone) return '—';
        // Удаляем все нецифровые символы
        const cleaned = ('' + phone).replace(/\D/g, '');

        // Проверяем длину и форматируем
        if (cleaned.length === 11) {
            return `+${cleaned[0]} (${cleaned.substring(1, 4)}) ${cleaned.substring(4, 7)}-${cleaned.substring(7, 9)}-${cleaned.substring(9, 11)}`;
        }
        return phone;
    }

    // Функция форматирования категории заявки
    function formatTicketCategory(category) {
        const categories = {
            "hardware": "Оборудование",
            "software": "Программное обеспечение",
            "network": "Сеть и интернет",
            "account": "Учетная запись",
            "other": "Другое",
        };
        return categories[category] || category;
    }

    // Функция получения класса для статуса
    function getStatusClass(status) {
        const statusClasses = {
            'open': 'bg-blue-100 text-blue-800',
            'in_progress': 'bg-yellow-100 text-yellow-800',
            'resolved': 'bg-green-100 text-green-800',
            'closed': 'bg-slate-100 text-slate-800'
        };
        return statusClasses[status] || 'bg-slate-100 text-slate-800';
    }

    // Функция для отображения уведомлений
    function showNotification(message, type = 'info', duration = 3000) {
        // Создаем элемент уведомления с иконкой
        const notificationElement = document.createElement('div');
        notificationElement.classList.add(
            'fixed', 'bottom-4', 'right-4', 'px-6', 'py-4', 'rounded-lg',
            'shadow-xl', 'z-[1000]', 'transform', 'transition-all',
            'duration-500', 'translate-y-20', 'opacity-0', 'flex',
            'items-center', 'gap-3', 'max-w-md'
        );

        // Выбираем иконку и цвета в зависимости от типа уведомления
        let iconSvg = '';
        if (type === 'success') {
            notificationElement.classList.add('bg-green-600', 'text-white');
            iconSvg = '<svg class="w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>';
        } else if (type === 'error') {
            notificationElement.classList.add('bg-red-600', 'text-white');
            iconSvg = '<svg class="w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>';
        } else if (type === 'info') {
            notificationElement.classList.add('bg-blue-600', 'text-white');
            iconSvg = '<svg class="w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" /></svg>';
        } else if (type === 'warning') {
            notificationElement.classList.add('bg-yellow-500', 'text-white');
            iconSvg = '<svg class="w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>';
        }

        notificationElement.innerHTML = `
            <div class="flex-shrink-0">
                ${iconSvg}
            </div>
            <div class="flex-grow">${message}</div>
            <div class="flex-shrink-0 ml-2">
                <button class="text-white opacity-70 hover:opacity-100 transition-opacity" onclick="this.parentNode.parentNode.remove()">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        `;

        // Убеждаемся, что на странице не более 3 уведомлений одновременно
        const existingNotifications = document.querySelectorAll('.notification-element');
        if (existingNotifications.length >= 3) {
            existingNotifications[0].remove();
        }

        // Добавляем класс для отслеживания и вставляем в DOM
        notificationElement.classList.add('notification-element');
        document.body.appendChild(notificationElement);

        // Анимируем появление
        setTimeout(() => {
            notificationElement.classList.remove('translate-y-20', 'opacity-0');
        }, 10);

        // Удаляем через указанное время
        const timeoutId = setTimeout(() => {
            notificationElement.classList.add('translate-y-20', 'opacity-0');
            setTimeout(() => {
                if (document.body.contains(notificationElement)) {
                    document.body.removeChild(notificationElement);
                }
            }, 500);
        }, duration);

        // Остановка таймера при наведении мыши
        notificationElement.addEventListener('mouseenter', () => {
            clearTimeout(timeoutId);
        });

        // Возобновление таймера при уходе мыши
        notificationElement.addEventListener('mouseleave', () => {
            setTimeout(() => {
                notificationElement.classList.add('translate-y-20', 'opacity-0');
                setTimeout(() => {
                    if (document.body.contains(notificationElement)) {
                        document.body.removeChild(notificationElement);
                    }
                }, 500);
            }, 1000);
        });
    }

    // Автоматическое применение фильтров
    if (filtersForm) {
        const selectInputs = filtersForm.querySelectorAll('select');
        selectInputs.forEach(input => {
            input.addEventListener('change', function() {
                if (liveUpdates) {
                    liveUpdates.refresh();
                }
            });
        });
    }

    // Очистка фильтров
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function() {
            if (filtersForm) {
                filtersForm.reset();
                if (liveUpdates) {
                    liveUpdates.refresh();
                }
            }
        });
    }

    // Поиск с задержкой
    const searchInput = document.getElementById('search');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                if (liveUpdates) {
                    liveUpdates.refresh();
                }
            }, 500);
        });
    }

    // Функция инициализации выпадающих меню
    function initTableDropdowns() {
        console.log('Инициализируем обработчики кнопок...');
        
        // Удаляем все старые обработчики
        document.querySelectorAll('.actions-btn').forEach(btn => {
            btn.removeEventListener('click', handleActionsClick);
        });

        // Добавляем обработчики для всех кнопок действий
        const buttons = document.querySelectorAll('.actions-btn');
        console.log('Найдено кнопок:', buttons.length);
        
        buttons.forEach(btn => {
            btn.addEventListener('click', handleActionsClick);
            console.log('Обработчик добавлен для кнопки:', btn.getAttribute('data-ticket-id'));
        });
    }

    // Обработчик клика по кнопке действий
    function handleActionsClick(e) {
        console.log('Кнопка нажата!');
        e.preventDefault();
        e.stopPropagation();

        const button = e.currentTarget;
        const ticketId = button.getAttribute('data-ticket-id');
        console.log('ID заявки:', ticketId);
        
        const menu = document.querySelector(`.actions-menu[data-ticket-id="${ticketId}"]`);
        console.log('Найдено меню:', menu);

        if (!menu) {
            console.error('Меню не найдено для заявки:', ticketId);
            return;
        }

        // Закрыть все другие меню
        document.querySelectorAll('.actions-menu').forEach(otherMenu => {
            if (otherMenu !== menu) {
                otherMenu.classList.add('hidden');
            }
        });

        document.querySelectorAll('.actions-btn').forEach(otherBtn => {
            if (otherBtn !== button) {
                otherBtn.classList.remove('bg-slate-100');
            }
        });

        // Переключить текущее меню
        const isHidden = menu.classList.contains('hidden');
        console.log('Меню скрыто:', isHidden);
        
        menu.classList.toggle('hidden');
        button.classList.toggle('bg-slate-100');
        
        console.log('Меню после переключения скрыто:', menu.classList.contains('hidden'));

        // Позиционирование меню
        if (!menu.classList.contains('hidden')) {
            const rect = button.getBoundingClientRect();
            const rightSpace = window.innerWidth - rect.right;

            menu.style.position = 'absolute';
            menu.style.zIndex = '1000';

            if (rightSpace < 200) {
                menu.style.left = 'auto';
                menu.style.right = '0';
            } else {
                menu.style.left = '0';
                menu.style.right = 'auto';
            }

            menu.style.top = 'calc(100% + 0.5rem)';
        }
    }

    // Инициализация LiveUpdates
    initLiveUpdates();

    // Инициализация выпадающих меню при загрузке страницы
    initTableDropdowns();

    // Начальная загрузка времени
    if (lastUpdated) {
        lastUpdated.textContent = `Загружено: ${new Date().toLocaleString('ru-RU')}`;
    }
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
