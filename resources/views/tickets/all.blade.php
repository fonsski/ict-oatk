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
            #tickets-container table,
            #tickets-container .overflow-x-auto,
            #tickets-container .overflow-auto {
                overflow-x: visible !important;
                overflow: visible !important;
            }
            #tickets-container table {
                width: 100% !important;
                table-layout: fixed !important;
            }
            #tickets-container td {
                word-wrap: break-word;
                overflow-wrap: break-word;
                padding: 24px !important;
                vertical-align: top;
                line-height: 1.5;
                max-width: 100%;
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
                -webkit-line-clamp: 3;
                min-height: 3em;
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

        <div class="flex justify-between items-center mt-6 mb-4">
            <h2 class="text-xl font-semibold text-slate-900">Заявки в системе <span class="text-slate-500 text-sm font-normal ml-2">{{ $tickets->total() }} записей</span></h2>
            <div class="flex items-center gap-3">
                <div id="last-updated-info" class="text-sm text-slate-500">
                    Обновлено: {{ now()->format('d.m.Y H:i') }}
                </div>
                <button type="button" id="refresh-button" class="refresh-button">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                    </svg>
                    Обновить данные
                </button>
            </div>
        </div>
        <div id="tickets-container" style="overflow-x: visible !important;">
            @if($tickets->count() > 0)
                <div class="bg-white rounded-lg border border-slate-200 shadow-sm" style="overflow: visible !important;">
                    <table class="w-full border-collapse" style="table-layout: fixed !important; overflow: visible !important; border-spacing: 0;">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-5 text-left text-sm font-semibold text-slate-900" style="width: 50%;">Заявка</th>
                                <th class="px-6 py-5 text-left text-sm font-semibold text-slate-900" style="width: 12%;">Заявитель</th>
                                <th class="px-6 py-5 text-left text-sm font-semibold text-slate-900" style="width: 8%;">Статус</th>
                                <th class="px-6 py-5 text-left text-sm font-semibold text-slate-900" style="width: 8%;">Приоритет</th>
                                <th class="px-6 py-5 text-left text-sm font-semibold text-slate-900" style="width: 10%;">Исполнитель</th>
                                <th class="px-6 py-5 text-left text-sm font-semibold text-slate-900" style="width: 7%;">Дата</th>
                                <th class="px-6 py-5 text-left text-sm font-semibold text-slate-900" style="width: 5%;">Действия</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200" id="tickets-tbody">
                            @include('tickets.partials.all-table-rows', ['tickets' => $tickets])
                        </tbody>
                    </table>
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
            <tr class="hover:bg-slate-50 transition-all duration-300" data-ticket-id="${ticket.id}">
                <td class="px-6 py-4">
                    <div>
                        <a href="${ticket.url}"
                           class="ticket-title line-clamp-2 break-words inline-block transition-all duration-300">
                            ${ticket.id ? `#${ticket.id}: ` : ''}${safeTitle}
                        </a>
                        <p class="ticket-description line-clamp-3 break-words">
                            ${safeDescription || 'Описание отсутствует'}
                        </p>
                        <div class="ticket-meta">
                            ${ticket.category ? `
                            <span class="ticket-meta-item">
                                <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                                ${ticket.category}
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
                <td class="px-6 py-5">
                    <div class="text-sm min-w-0">
                        <div class="font-medium text-slate-900 truncate mb-1 flex items-center" title="${ticket.reporter_name || '—'}">
                            <svg class="w-4 h-4 mr-1.5 text-slate-400 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd" />
                            </svg>
                            <span class="truncate">${ticket.reporter_name || '—'}</span>
                        </div>
                        <div class="text-slate-600 truncate flex items-center" title="${ticket.reporter_phone || '—'}">
                            <svg class="w-4 h-4 mr-1.5 text-slate-400 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                            </svg>
                            <span class="truncate">${ticket.reporter_phone ? formatPhone(ticket.reporter_phone) : '—'}</span>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-5">
                    <span class="inline-flex items-center justify-center px-3 py-1.5 rounded-full text-xs font-medium ${getStatusClass(ticket.status)}" style="min-width: 100px; text-align: center; white-space: nowrap;">
                        ${getStatusLabel(ticket.status)}
                    </span>
                </td>
                <td class="px-6 py-5">
                    <span class="inline-flex items-center justify-center px-3 py-1.5 rounded-full text-xs font-medium ${getStatusClass(ticket.status)}" style="min-width: 90px; text-align: center;">
                        ${getStatusLabel(ticket.status)}
                    </span>
                </td>
                <td class="px-6 py-5">
                    <span class="inline-flex items-center justify-center px-3 py-1.5 rounded-full text-xs font-medium ${ticket.priority === 'urgent' ? 'bg-red-100 text-red-800' : ticket.priority === 'high' ? 'bg-orange-100 text-orange-800' : ticket.priority === 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'}" style="min-width: 100px; text-align: center; white-space: nowrap;">
                        ${ticket.priority === 'urgent' ? 'Срочный' : ticket.priority === 'high' ? 'Высокий' : ticket.priority === 'medium' ? 'Средний' : ticket.priority === 'low' ? 'Низкий' : ticket.priority}
                    </span>
                </td>
                <td class="px-6 py-5">
                    <div class="text-sm flex items-start gap-1.5 max-w-[150px]" title="${ticket.assigned_to || 'Не назначено'}">
                        ${ticket.assigned_to ?
                            `<svg class="w-4 h-4 mt-0.5 text-slate-500 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                            </svg>
                            <span class="truncate font-medium text-slate-700">${ticket.assigned_to}</span>`
                            :
                            `<svg class="w-4 h-4 mt-0.5 text-slate-300 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd" />
                            </svg>
                            <span class="italic text-slate-400 truncate">Не назначено</span>`
                        }
                    </div>
                </td>
                <td class="px-6 py-5 whitespace-nowrap">
                    <div class="text-sm">
                        <div class="whitespace-nowrap flex items-center text-slate-700 font-medium" title="${ticket.created_at}">
                            <svg class="w-4 h-4 mr-1.5 text-slate-500 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                            </svg>
                            ${formatDate(ticket.created_at)}
                        </div>
                        ${ticket.updated_at && ticket.updated_at !== ticket.created_at ?
                            `<div class="text-xs text-slate-500 mt-1.5 flex items-center">
                                <svg class="w-3.5 h-3.5 mr-1 text-slate-400 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                                </svg>
                                Обн. ${formatDate(ticket.updated_at).split(' ')[0]}
                            </div>` : ''}
                    </div>
                </td>
                <td class="px-6 py-5 whitespace-nowrap">
                    <div class="flex items-center gap-1">
                        <a href="${ticket.url}"
                           class="inline-flex items-center justify-center px-3 py-1.5 bg-blue-50 text-blue-700 rounded hover:bg-blue-100 transition text-xs font-medium shadow-sm">
                            <svg class="w-3.5 h-3.5 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                            </svg>
                            Просмотр
                        </a>
                        <div class="relative inline-block text-left ml-1" data-dropdown-id="${ticket.id}">
                            <button type="button" class="actions-menu-button p-1.5 rounded hover:bg-slate-200 focus:outline-none border border-transparent hover:border-slate-300" aria-label="Действия" data-id="${ticket.id}">
                                <svg class="w-4 h-4 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                </svg>
                            </button>
                            <div class="actions-menu hidden absolute right-0 mt-2 w-56 bg-white rounded-md shadow-xl ring-1 ring-black ring-opacity-5 focus:outline-none border border-gray-200 z-50" style="min-width: 12rem; max-width: 16rem; transform: translateY(0);" role="menu" aria-orientation="vertical" aria-labelledby="menu-button" tabindex="-1">
                                <div class="py-1" role="none">
                                    <a href="${ticket.url}" class="block px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 border-b border-gray-100" role="menuitem">Просмотр заявки</a>
                                    ${ticket.status !== 'in_progress' ? `<button type="button" class="single-action block w-full text-left px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" data-action="change-status" data-id="${ticket.id}" data-status="in_progress" role="menuitem">Взять в работу</button>` : ''}
                                    ${ticket.status !== 'resolved' ? `<button type="button" class="single-action block w-full text-left px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" data-action="change-status" data-id="${ticket.id}" data-status="resolved" role="menuitem">Отметить как решенную</button>` : ''}
                                    ${ticket.status !== 'closed' ? `<button type="button" class="single-action block w-full text-left px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" data-action="change-status" data-id="${ticket.id}" data-status="closed" role="menuitem">Закрыть заявку</button>` : ''}
                                    <button type="button" class="single-action block w-full text-left px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" data-action="assign-to" data-id="${ticket.id}" role="menuitem">Назначить исполнителя</button>
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

            // Сбрасываем стили позиционирования перед установкой новых
            dropdown.style.left = '';
            dropdown.style.right = '';
            dropdown.style.top = '';
            dropdown.style.position = 'absolute';
            dropdown.style.zIndex = '100';

            // Корректное позиционирование меню
            const rect = button.getBoundingClientRect();

            // Проверяем, достаточно ли места справа от кнопки
            const rightSpace = window.innerWidth - rect.right;

            if (rightSpace < dropdown.offsetWidth) {
                // Недостаточно места справа, позиционируем слева от кнопки
                dropdown.style.left = 'auto';
                dropdown.style.right = '0';
            } else {
                // Достаточно места справа, позиционируем как обычно
                dropdown.style.left = '0';
                dropdown.style.right = 'auto';
            }

            // Устанавливаем позицию по вертикали
            dropdown.style.top = 'calc(100% + 0.5rem)';

            // Максимальная высота и прокрутка для больших меню
            dropdown.style.maxHeight = '80vh';
            dropdown.style.overflowY = 'auto';

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

            // Визуальный эффект нажатия и текст кнопки
            button.classList.add('bg-gray-100');
            const buttonText = button.textContent.trim();

            // Показываем мини-уведомление внутри кнопки
            const originalText = button.innerHTML;
            button.innerHTML = '<span class="flex items-center justify-center gap-2"><svg class="animate-spin h-4 w-4 text-gray-800" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Выполняется...</span>';

            // Закрыть меню немедленно, чтобы пользователь видел, что клик обработан
            const dropdown = button.closest('.actions-menu');
            if (dropdown) {
                dropdown.classList.add('hidden');
            }

            // Находим и убираем подсветку с кнопки меню
            const dropdownContainer = button.closest('[data-dropdown-id]');
            if (dropdownContainer) {
                const menuButton = dropdownContainer.querySelector('.actions-menu-button');
                if (menuButton) {
                    menuButton.classList.remove('bg-slate-200');
                }
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
