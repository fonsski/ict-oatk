@extends('layouts.app')

@section('title', 'Главная - ICT Help')

@section('content')
<div class="page home">
    @if(session('success'))
    <div class="notice notice-success" role="alert">{{ session('success') }}</div>
    @endif

    @guest
    <!-- Первый экран гостя: что это, что делать и как это устроено -->
    <div class="intro-grid">
        <section class="intro">
            <h1 class="intro-title">Заявка в службу поддержки колледжа</h1>
            <p class="intro-text">Опишите проблему и укажите кабинет: техник возьмёт заявку в работу, а ход выполнения будет виден в разделе «Мои заявки». Регистрация не нужна.</p>
            <div class="intro-actions">
                <a href="{{ route('tickets.create') }}" class="btn-primary">Подать заявку</a>
                <a href="{{ route('login') }}" class="btn-secondary">Войти</a>
            </div>
            <p class="intro-note">Срочно? Звоните <a href="tel:+79503362989">+7 (950) 336-29-89</a>, пн–пт 8:30–17:00.</p>
        </section>

        <section class="steps">
            <h2 class="section-title">Как это работает</h2>
            <ol class="steps-list">
                <li>
                    <h3>Опишите проблему</h3>
                    <p>Укажите кабинет и оборудование, коротко расскажите, что случилось.</p>
                </li>
                <li>
                    <h3>Заявку возьмёт техник</h3>
                    <p>В «Моих заявках» видно, кто назначен исполнителем и в каком статусе заявка.</p>
                </li>
                <li>
                    <h3>Решение и закрытие</h3>
                    <p>Когда проблема устранена, заявка получает статус «Решена», затем «Закрыта».</p>
                </li>
            </ol>
        </section>
    </div>
    @endguest

    @auth
    <div class="page-head">
        <h1 class="page-title">Главная</h1>
    </div>
    @endauth

    <!-- Активные заявки для сотрудников -->
    @if(user_can_manage_tickets())
    <section class="card home-tickets">
        <div class="list-head list-head--wrap">
            <h2 class="list-title">Активные заявки</h2>
            <div class="page-actions">
                <div class="freshness">
                    <span class="freshness-dot bg-green-500" id="tech-status-indicator"></span>
                    <span id="tech-last-updated">Загружено</span>
                </div>
                <button id="tech-refresh-btn" class="btn-secondary btn-sm">
                    <svg class="w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="23,4 23,10 17,10"></polyline>
                        <path d="M20.49,15a9,9,0,1,1-2.12-9.36L23,10"></path>
                    </svg>
                    Обновить
                </button>
                <button id="test-api-btn" class="btn-secondary btn-sm">Тест API</button>
                <a href="{{ route('all-tickets.index') }}" class="btn-primary btn-sm">Все заявки</a>
            </div>
        </div>

        @if(isset($ticketStats))
        <div class="kpi-strip">
            <div class="kpi-cell">
                <span class="kpi-label">Всего</span>
                <span class="kpi-value" id="tech-total-count">{{ $ticketStats['total'] }}</span>
            </div>
            <div class="kpi-cell">
                <span class="kpi-label">Открытые</span>
                <span class="kpi-value" id="tech-open-count">{{ $ticketStats['open'] }}</span>
            </div>
            <div class="kpi-cell">
                <span class="kpi-label">В работе</span>
                <span class="kpi-value" id="tech-progress-count">{{ $ticketStats['in_progress'] }}</span>
            </div>
            <div class="kpi-cell">
                <span class="kpi-label">Решённые</span>
                <span class="kpi-value" id="tech-resolved-count">{{ $ticketStats['resolved'] }}</span>
            </div>
            <div class="kpi-cell">
                <span class="kpi-label">Закрытые</span>
                <span class="kpi-value" id="tech-closed-count">{{ $ticketStats['closed'] }}</span>
            </div>
        </div>
        @endif

        <div class="overflow-x-auto">
            <table class="min-w-[700px] w-full">
                <thead class="border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-2.5 text-left">Заявка</th>
                        <th class="px-4 py-2.5 text-left">Статус</th>
                        <th class="px-4 py-2.5 text-left">Приоритет</th>
                        <th class="px-4 py-2.5 text-left">Заявитель</th>
                        <th class="px-4 py-2.5 text-left">Исполнитель</th>
                        <th class="px-4 py-2.5 text-left">Дата</th>
                        <th class="px-4 py-2.5 text-left">Действия</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200" id="tech-tickets-tbody">
                    @if(isset($tickets) && $tickets->count() > 0)
                        @foreach($tickets->take(10) as $ticket)
                        <tr>
                            <td class="px-4 py-3">
                                <a href="{{ route('tickets.show', $ticket) }}" class="text-slate-900 font-medium hover:text-blue-600 transition-colors duration-200 break-words max-w-xs inline-block" title="{{ $ticket->title }}">
                                    <span class="line-clamp-1">{{ Str::limit($ticket->title, 50) }}</span>
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $statusColors = [
                                        'open' => 'bg-blue-100 text-blue-800',
                                        'in_progress' => 'bg-yellow-100 text-yellow-800',
                                        'resolved' => 'bg-green-100 text-green-800',
                                        'closed' => 'bg-slate-100 text-slate-800'
                                    ];
                                    $statusLabels = [
                                        'open' => 'Открыта',
                                        'in_progress' => 'В работе',
                                        'resolved' => 'Решена',
                                        'closed' => 'Закрыта'
                                    ];
                                @endphp
                                <span class="inline-flex items-center whitespace-nowrap px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$ticket->status] ?? 'bg-slate-100 text-slate-800' }}" title="Статус: {{ $statusLabels[$ticket->status] ?? $ticket->status }}">
                                    {{ $statusLabels[$ticket->status] ?? $ticket->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center whitespace-nowrap px-2 py-0.5 rounded-full text-xs font-medium {{ $ticket->priority == 'urgent' ? 'bg-red-200 text-red-900' : get_priority_badge_class($ticket->priority) }}" title="Приоритет: {{ $ticket->priority == 'urgent' ? 'Срочный' : format_ticket_priority($ticket->priority) }}">
                                    {{ $ticket->priority == 'urgent' ? 'Срочный' : format_ticket_priority($ticket->priority) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm text-slate-900" title="{{ $ticket->reporter_name ?: '—' }}">{{ $ticket->reporter_name ?: '—' }}</div>
                            </td>
                            <td class="px-4 py-3">
                                @if($ticket->assignedTo)
                                    <div class="text-sm text-slate-900" title="{{ $ticket->assignedTo->name }}">{{ $ticket->assignedTo->name }}</div>
                                @else
                                    <span class="text-sm text-slate-500" title="Исполнитель не назначен">Не назначен</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm text-slate-600">{{ $ticket->created_at->format('d.m H:i') }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center">
                                    <div class="relative z-50" data-dropdown>
                                        <button type="button" class="text-slate-500 hover:text-slate-700 p-2 transition-colors duration-150 rounded-full hover:bg-slate-100" data-dropdown-toggle title="Действия">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
                                            </svg>
                                        </button>
                                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg border border-slate-200 z-50 hidden animate-fade-in" data-dropdown-menu style="min-width: 10rem; max-width: 12rem;">
                                            <div class="py-1">
                                                <a href="{{ route('tickets.show', $ticket) }}" class="block px-4 py-3 text-sm text-slate-700 hover:bg-slate-100 transition">Просмотр заявки</a>
                                                @if($ticket->status === 'open' && Auth::check() && Auth::user()->role && in_array(Auth::user()->role->slug, ['admin', 'master', 'technician']))
                                                    <form action="{{ route('tickets.start', $ticket) }}" method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit" class="block w-full text-left px-4 py-3 text-sm text-slate-700 hover:bg-slate-100 transition">
                                                            Взять в работу
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-slate-500">
                                <p class="text-sm font-medium text-slate-900 mb-1">Пока нет заявок</p>
                                <p class="text-sm">Новые заявки будут появляться здесь автоматически.</p>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </section>
    @endif

    <!-- Частые вопросы: доступны всем, включая неавторизованных посетителей -->
    <section class="faq">
        <div class="faq-head">
            <h2 class="section-title">Часто возникающие проблемы</h2>
            @if(user_can_manage_faq())
            <div class="page-actions">
                <a href="{{ route('homepage-faq.index') }}" class="btn-secondary btn-sm">Управлять</a>
                <a href="{{ route('homepage-faq.create') }}" class="btn-primary btn-sm">Добавить вопрос</a>
            </div>
            @endif
        </div>

        @if(isset($faqs) && $faqs->count())
        <div class="faq-list">
            @foreach($faqs as $faq)
            <a href="{{ route('homepage-faq.show', $faq->slug) }}" class="faq-row">
                <div class="faq-row-text">
                    <h3 class="faq-title">{{ $faq->title }}</h3>
                    <p class="faq-excerpt line-clamp-2">{{ $faq->excerpt ?? Str::limit(strip_tags($faq->content), 120) }}</p>
                </div>
                <span class="faq-date">{{ $faq->created_at ? $faq->created_at->format('d.m.Y') : '—' }}</span>
            </a>
            @endforeach
        </div>
        @else
        <p class="empty-note">Пока нет часто задаваемых вопросов@if(user_can_manage_faq()): <a href="{{ route('homepage-faq.create') }}" class="text-blue-600 hover:underline underline-offset-2">добавьте первый</a>@else: ответы появятся здесь, когда их добавят@endif.</p>
        @endif
    </section>

    @if(user_can_manage_tickets())
    @push('scripts')
    <script src="{{ Vite::asset('resources/js/live-updates.js') }}"></script>
    <script src="{{ Vite::asset('resources/js/smart-updates.js') }}"></script>
    <script>
    const canManageTickets = {{ user_can_manage_tickets() ? 'true' : 'false' }};
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

    // Объявляем переменные в глобальной области видимости
    let techRefreshBtn, techStatusIndicator, techLastUpdated, techTicketsContainer;
    let liveUpdates;
    // Резервный опрос раз в 30 секунд: мгновенные обновления доски приходят
    // через Reverb (событие realtime:tickets), поэтому частый поллинг не нужен.
    const TECH_REFRESH_INTERVAL = 30000;

    // Функция инициализации панели техника
    function initTechnicianDashboard() {
        techRefreshBtn = document.getElementById('tech-refresh-btn');
        techStatusIndicator = document.getElementById('tech-status-indicator');
        techLastUpdated = document.getElementById('tech-last-updated');
        techTicketsContainer = document.getElementById('tech-tickets-container');

        async function refreshTechTickets() {
            try {
                const response = await fetch('{{ route("home.technician.tickets") }}', {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    cache: 'no-store',
                    credentials: 'same-origin'
                });

                if (!response.ok) {
                    if (response.status === 401 || response.status === 403) {
                        window.location.href = '/login';
                        return;
                    }
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();

                if (data.error) throw new Error(data.error);

                if (data.stats) {
                    const totalEl = document.getElementById('tech-total-count');
                    const openEl = document.getElementById('tech-open-count');
                    const progressEl = document.getElementById('tech-progress-count');
                    const resolvedEl = document.getElementById('tech-resolved-count');
                    const closedEl = document.getElementById('tech-closed-count');

                    if (totalEl) totalEl.textContent = data.stats.total;
                    if (openEl) openEl.textContent = data.stats.open;
                    if (progressEl) progressEl.textContent = data.stats.in_progress;
                    if (resolvedEl) resolvedEl.textContent = data.stats.resolved;
                    if (closedEl) closedEl.textContent = data.stats.closed;
                }

                if (data.tickets && Array.isArray(data.tickets)) {
                    if (data.tickets.length > 0) {
                        updateTechTicketsTable(data.tickets.slice(0, 10));
                    } else {
                        updateTechTicketsTable([]);
                    }
                } else {
                    updateTechTicketsTable([]);
                }

                if (techLastUpdated) techLastUpdated.textContent = `Обновлено: ${data.last_updated}`;
                if (techStatusIndicator) techStatusIndicator.className = 'w-2 h-2 bg-green-500 rounded-full';

            } catch (error) {
                if (techStatusIndicator) {
                    techStatusIndicator.className = 'w-2 h-2 bg-red-500 rounded-full';
                    setTimeout(() => {
                        if (techStatusIndicator) {
                            techStatusIndicator.className = 'w-2 h-2 bg-green-500 rounded-full';
                        }
                    }, 30000);
                }
                if (techLastUpdated) techLastUpdated.textContent = 'Ошибка обновления';

                if (error.message.includes('401') || error.message.includes('403') || error.message.includes('Unauthorized')) {
                    setTimeout(() => {
                        window.location.href = '/login';
                    }, 1000);
                }
            }
        }

        function updateTechStats(stats) {
            const totalEl = document.getElementById('tech-total-count');
            const openEl = document.getElementById('tech-open-count');
            const progressEl = document.getElementById('tech-progress-count');
            const resolvedEl = document.getElementById('tech-resolved-count');
            const closedEl = document.getElementById('tech-closed-count');

            if (totalEl) totalEl.textContent = stats.total;
            if (openEl) openEl.textContent = stats.open;
            if (progressEl) progressEl.textContent = stats.in_progress;
            if (resolvedEl) resolvedEl.textContent = stats.resolved;
            if (closedEl) closedEl.textContent = stats.closed;
        }

        // Умная система обновления для заявок техника
        let techSmartUpdates;

        function updateTechTicketsTable(tickets) {
            const tbody = document.getElementById('tech-tickets-tbody');
            if (!tbody) {
                return;
            }

            try {
                if (tickets.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center">
                                <div class="text-gray-500">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <p class="mt-2 text-sm font-medium">Нет активных заявок</p>
                                </div>
                            </td>
                        </tr>
                    `;
                    if (techSmartUpdates) techSmartUpdates.clear();
                    return;
                }

                // Проверяем доступность SmartUpdates
                if (typeof SmartUpdates === 'undefined') {
                    console.warn('SmartUpdates не загружен, используем fallback');
                    // Fallback к старому методу
                    tbody.innerHTML = tickets.map(ticket => createTechTicketRowHTML(ticket)).join('');
                    setTimeout(() => {
                        if (typeof initTableDropdowns === 'function') {
                            initTableDropdowns();
                        }
                    }, 100);
                    return;
                }

                // Инициализируем SmartUpdates если еще не инициализирован
                if (!techSmartUpdates) {
                    techSmartUpdates = new SmartUpdates({
                        containerSelector: '#tech-tickets-tbody',
                        itemSelector: 'tr[data-ticket-id]',
                        itemIdAttribute: 'data-ticket-id',
                        createItemHTML: createTechTicketRowHTML,
                        fieldsToCheck: ['status', 'priority', 'assigned_to_name', 'title', 'description'],
                        preserveState: true,
                        onItemUpdate: function(ticket) {
                            // Переинициализируем обработчики для обновленной строки
                            setTimeout(() => {
                                if (typeof initTableDropdowns === 'function') {
                                    initTableDropdowns();
                                }
                            }, 100);
                        },
                        onItemAdd: function(ticket) {
                            // Переинициализируем обработчики для новой строки
                            setTimeout(() => {
                                if (typeof initTableDropdowns === 'function') {
                                    initTableDropdowns();
                                }
                            }, 100);
                        }
                    });
                }

                // Обновляем данные
                techSmartUpdates.updateData(tickets);
            } catch (error) {
                // Создаем tbody с сообщением об ошибке
                const errorTbody = document.createElement('tbody');
                errorTbody.className = 'divide-y divide-slate-200';
                const errorRow = createErrorRow();
                errorTbody.appendChild(errorRow);

                tbody.parentNode.replaceChild(errorTbody, tbody);
            }
        }

        function createEmptyRow() {
            const row = document.createElement('tr');

            const cell = document.createElement('td');
            cell.colSpan = 7;
            cell.className = 'px-4 py-8 text-center text-slate-500';

            const container = document.createElement('div');
            container.className = 'flex flex-col items-center';

            const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            svg.className = 'w-12 h-12 text-slate-400 mb-4';
            svg.setAttribute('fill', 'none');
            svg.setAttribute('viewBox', '0 0 24 24');
            svg.setAttribute('stroke', 'currentColor');

            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('stroke-linecap', 'round');
            path.setAttribute('stroke-linejoin', 'round');
            path.setAttribute('stroke-width', '2');
            path.setAttribute('d', 'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2 2m16-7H4m16 0l-2-2m2 2l-2 2M4 13l2-2m-2 2l2 2');
            svg.appendChild(path);

            const title = document.createElement('p');
            title.className = 'text-lg font-medium mb-1';
            title.textContent = 'Пока нет заявок';

            const subtitle = document.createElement('p');
            subtitle.className = 'text-sm';
            subtitle.textContent = 'Новые заявки будут появляться здесь автоматически';

            container.appendChild(svg);
            container.appendChild(title);
            container.appendChild(subtitle);
            cell.appendChild(container);
            row.appendChild(cell);

            return row;
        }

        function createErrorRow() {
            const row = document.createElement('tr');

            const cell = document.createElement('td');
            cell.colSpan = 7;
            cell.className = 'px-4 py-8 text-center text-red-500';

            const title = document.createElement('p');
            title.textContent = 'Ошибка при обновлении таблицы';

            const subtitle = document.createElement('p');
            subtitle.className = 'text-sm';
            subtitle.textContent = 'Проверьте консоль браузера для деталей';

            cell.appendChild(title);
            cell.appendChild(subtitle);
            row.appendChild(cell);

            return row;
        }

        // Функция создания HTML строки для SmartUpdates
        function createTechTicketRowHTML(ticket) {
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

            const title = ticket.title || 'Без названия';
            const truncatedTitle = title.length > 40 ? title.substring(0, 40) + '...' : title;

            let actionsMenu = '';
            if (ticket.status === 'open' && userRole && ['admin', 'master', 'technician'].includes(userRole)) {
                actionsMenu = `
                    <a href="${ticket.url || '#'}" class="block px-4 py-3 text-sm text-slate-700 hover:bg-slate-100 transition">Просмотр заявки</a>
                    <form action="/tickets/${ticket.id}/start" method="POST" class="inline" onsubmit="return confirm('Взять заявку в работу?');">
                        <input type="hidden" name="_token" value="${csrfToken}">
                        <button type="submit" class="block w-full text-left px-4 py-3 text-sm text-slate-700 hover:bg-slate-100 transition">Взять в работу</button>
                    </form>
                `;
            } else {
                actionsMenu = `<a href="${ticket.url || '#'}" class="block px-4 py-3 text-sm text-slate-700 hover:bg-slate-100 transition">Просмотр заявки</a>`;
            }

            return `
                <tr data-ticket-id="${ticket.id}">
                    <td class="px-4 py-3">
                        <div>
                            <a href="${ticket.url || '#'}" class="text-slate-900 font-medium hover:text-blue-600 transition-colors duration-200 break-words max-w-xs inline-block" title="${ticket.title || ''}">
                                <span class="line-clamp-1">${truncatedTitle}</span>
                            </a>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center whitespace-nowrap px-2 py-1 rounded-full text-xs font-medium ${statusColors[ticket.status] || 'bg-slate-100 text-slate-800'}" title="Статус: ${statusLabels[ticket.status] || ticket.status}">
                            ${statusLabels[ticket.status] || ticket.status}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center whitespace-nowrap px-2 py-1 rounded-full text-xs font-medium ${priorityColors[ticket.priority] || 'bg-slate-100 text-slate-800'}" title="Приоритет: ${priorityLabels[ticket.priority] || ticket.priority}">
                            ${priorityLabels[ticket.priority] || ticket.priority}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-sm text-slate-900" title="${ticket.reporter_name || '—'}">${ticket.reporter_name || '—'}</div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-sm text-slate-900" title="${ticket.assigned_to_name || '—'}">${ticket.assigned_to_name || '—'}</div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-sm text-slate-500" title="${ticket.created_at || '—'}">${ticket.created_at || '—'}</div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-center">
                            <div class="relative z-50" data-dropdown>
                                <button type="button" class="text-slate-500 hover:text-slate-700 p-2 transition-all duration-300 rounded-full hover:bg-slate-100" data-dropdown-toggle title="Действия">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
                                    </svg>
                                </button>
                                <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-xl border border-slate-200 z-50 hidden animate-fade-in" data-dropdown-menu style="min-width: 10rem; max-width: 12rem;">
                                    <div class="py-1">
                                        ${actionsMenu}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            `;
        }

        function createTechTicketRowElement(ticket) {
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

            // Жестко заданные цвета приоритетов
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

            const title = ticket.title || 'Без названия';
            const truncatedTitle = title.length > 40 ? title.substring(0, 40) + '...' : title;

            // Создаем элемент строки
            const row = document.createElement('tr');
            row.setAttribute('data-ticket-id', ticket.id);

            // Ячейка с названием заявки
            const titleCell = document.createElement('td');
            titleCell.className = 'px-4 py-3';

            const titleDiv = document.createElement('div');

            const titleLink = document.createElement('a');
            titleLink.href = ticket.url || '#';
            titleLink.className = 'text-slate-900 font-medium hover:text-blue-600 transition-colors duration-200 break-words max-w-xs inline-block';
            titleLink.title = ticket.title || '';

            const titleSpan = document.createElement('span');
            titleSpan.className = 'line-clamp-1';
            titleSpan.textContent = truncatedTitle;
            titleLink.appendChild(titleSpan);

            titleDiv.appendChild(titleLink);
            titleCell.appendChild(titleDiv);

            // Ячейка статуса
            const statusCell = document.createElement('td');
            statusCell.className = 'px-4 py-3';

            const statusSpan = document.createElement('span');
            statusSpan.className = `inline-flex items-center whitespace-nowrap px-2 py-1 rounded-full text-xs font-medium ${statusColors[ticket.status] || 'bg-slate-100 text-slate-800'}`;
            statusSpan.textContent = statusLabels[ticket.status] || ticket.status;
            statusSpan.title = `Статус: ${statusLabels[ticket.status] || ticket.status}`;

            statusCell.appendChild(statusSpan);

            // Ячейка приоритета
            const priorityCell = document.createElement('td');
            priorityCell.className = 'px-4 py-3';

            const prioritySpan = document.createElement('span');
            if (ticket.priority === 'urgent') {
                prioritySpan.className = 'inline-flex items-center whitespace-nowrap px-2 py-1 rounded-full text-xs font-medium bg-red-200 text-red-900';
                prioritySpan.textContent = 'Срочный';
                prioritySpan.title = 'Приоритет: Срочный';
            } else {
                prioritySpan.className = `inline-flex items-center whitespace-nowrap px-2 py-1 rounded-full text-xs font-medium ${priorityColors[ticket.priority] || 'bg-slate-100 text-slate-800'}`;
                prioritySpan.textContent = priorityLabels[ticket.priority] || ticket.priority;
                prioritySpan.title = `Приоритет: ${priorityLabels[ticket.priority] || ticket.priority}`;
            }

            priorityCell.appendChild(prioritySpan);

            // Ячейка заявителя
            const reporterCell = document.createElement('td');
            reporterCell.className = 'px-4 py-3';

            const reporterDiv = document.createElement('div');
            reporterDiv.className = 'text-sm text-slate-900';
            reporterDiv.textContent = ticket.reporter_name || '—';
            reporterDiv.title = ticket.reporter_name || '—';

            reporterCell.appendChild(reporterDiv);

            // Ячейка исполнителя
            const assignedCell = document.createElement('td');
            assignedCell.className = 'px-4 py-3';

            if (ticket.assigned_to && ticket.assigned_to.name) {
                const assignedDiv = document.createElement('div');
                assignedDiv.className = 'text-sm text-slate-900';
                assignedDiv.textContent = ticket.assigned_to.name;
                assignedDiv.title = ticket.assigned_to.name;
                assignedCell.appendChild(assignedDiv);
            } else {
                const assignedSpan = document.createElement('span');
                assignedSpan.className = 'text-sm text-slate-500 italic';
                assignedSpan.textContent = 'Не назначен';
                assignedSpan.title = 'Исполнитель не назначен';
                assignedCell.appendChild(assignedSpan);
            }

            // Ячейка даты
            const dateCell = document.createElement('td');
            dateCell.className = 'px-4 py-3';

            const dateDiv = document.createElement('div');
            dateDiv.className = 'text-sm text-slate-600';
            dateDiv.textContent = ticket.created_at || '—';

            dateCell.appendChild(dateDiv);

            // Ячейка действий
            const actionsCell = document.createElement('td');
            actionsCell.className = 'px-4 py-3';

            const actionsDiv = document.createElement('div');
            actionsDiv.className = 'flex items-center gap-2';

            // Создаем выпадающее меню
            const dropdown = document.createElement('div');
            dropdown.className = 'relative z-50';
            dropdown.setAttribute('data-dropdown', '');

            const toggleButton = document.createElement('button');
            toggleButton.type = 'button';
            toggleButton.className = 'text-slate-500 hover:text-slate-700 p-2 transition-all duration-300 rounded-full hover:bg-slate-100';
            toggleButton.setAttribute('data-dropdown-toggle', '');
            toggleButton.title = 'Действия';

            const toggleIcon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            toggleIcon.className = 'w-5 h-5';
            toggleIcon.setAttribute('fill', 'currentColor');
            toggleIcon.setAttribute('viewBox', '0 0 20 20');
            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('d', 'M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z');
            toggleIcon.appendChild(path);
            toggleButton.appendChild(toggleIcon);

            const dropdownMenu = document.createElement('div');
            dropdownMenu.className = 'absolute right-0 mt-2 w-48 bg-white rounded-md shadow-xl border border-slate-200 z-50 hidden animate-fade-in';
            dropdownMenu.setAttribute('data-dropdown-menu', '');
            dropdownMenu.style.minWidth = '10rem';
            dropdownMenu.style.maxWidth = '12rem';

            const menuContent = document.createElement('div');
            menuContent.className = 'py-1';

            // Ссылка на просмотр
            const viewLink = document.createElement('a');
            viewLink.href = ticket.url || '#';
            viewLink.className = 'block px-4 py-3 text-sm text-slate-700 hover:bg-slate-100 transition';
            viewLink.textContent = 'Просмотр заявки';
            menuContent.appendChild(viewLink);

            // Кнопка "Взять в работу" для открытых заявок
            if (ticket.status === 'open' && userRole && ['admin', 'master', 'technician'].includes(userRole)) {
                const startForm = document.createElement('form');
                startForm.method = 'POST';
                startForm.action = `/tickets/${ticket.id}/start`;
                startForm.className = 'inline';
                startForm.onsubmit = () => confirm('Взять заявку в работу?');

                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = csrfToken;

                const startButton = document.createElement('button');
                startButton.type = 'submit';
                startButton.className = 'block w-full text-left px-4 py-3 text-sm text-slate-700 hover:bg-slate-100 transition';
                startButton.textContent = 'Взять в работу';

                startForm.appendChild(csrfInput);
                startForm.appendChild(startButton);
                menuContent.appendChild(startForm);
            }

            dropdownMenu.appendChild(menuContent);
            dropdown.appendChild(toggleButton);
            dropdown.appendChild(dropdownMenu);
            actionsDiv.appendChild(dropdown);

            actionsCell.appendChild(actionsDiv);

            // Добавляем все ячейки в строку
            row.appendChild(titleCell);
            row.appendChild(statusCell);
            row.appendChild(priorityCell);
            row.appendChild(reporterCell);
            row.appendChild(assignedCell);
            row.appendChild(dateCell);
            row.appendChild(actionsCell);

            return row;
        }

        // Start event listeners setup
        function setupEventListeners() {
            if (techRefreshBtn) {
                techRefreshBtn.addEventListener('click', function() {
                    if (liveUpdates) {
                        liveUpdates.refresh();
                    }
                });
            }

        } // End of setupEventListeners function

        function startTechAutoRefresh() {
            techRefreshInterval = setInterval(() => {
                refreshTechTickets();
            }, TECH_REFRESH_INTERVAL);
        }

        function stopTechAutoRefresh() {
            if (techRefreshInterval) {
                clearInterval(techRefreshInterval);
                techRefreshInterval = null;
            }
        }

        if (canManageTickets) {
            if (typeof LiveUpdates === 'undefined') {
                // Fallback к старому методу
                refreshTechTickets();
                startTechAutoRefresh();
            } else {
                // Инициализируем LiveUpdates
                liveUpdates = new LiveUpdates({
                    refreshInterval: TECH_REFRESH_INTERVAL,
                    apiEndpoint: '{{ route("home.technician.tickets") }}',
                    csrfToken: csrfToken,
                    onSuccess: function(data) {
                        // Обновляем статистику
                        if (data.stats) {
                            updateTechStats(data.stats);
                        }

                        // Обновляем таблицу заявок
                        if (data.tickets && Array.isArray(data.tickets)) {
                            updateTechTicketsTable(data.tickets.slice(0, 10));
                        }
                    },
                    onError: function(error) {
                        // Обработка ошибки
                    }
                });
            }

            if (techLastUpdated) {
                techLastUpdated.textContent = `Загружено: ${new Date().toLocaleString('ru-RU')}`;
            }

            // Мгновенное обновление доски по push от Reverb (realtime.js).
            window.addEventListener('realtime:tickets', function() {
                if (liveUpdates) {
                    liveUpdates.refresh();
                } else {
                    refreshTechTickets();
                }
            });
        }

        // Initialize event listeners
        setupEventListeners();
    }

    // Функция инициализации выпадающих меню (из all-table-rows.blade.php)
    function initTableDropdowns() {
        // Обработка выпадающих меню в таблице
        document.querySelectorAll('[data-dropdown]').forEach(function(dropdown) {
            // Функция вызывается после каждого обновления списка — не навешиваем
            // обработчик повторно, иначе накапливаются дубли и меню «мигает».
            if (dropdown.dataset.dropdownBound === '1') {
                return;
            }
            dropdown.dataset.dropdownBound = '1';

            const toggle = dropdown.querySelector('[data-dropdown-toggle]');
            const menu = dropdown.querySelector('[data-dropdown-menu]');

            if (!toggle || !menu) {
                return;
            }

            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const willOpen = menu.classList.contains('hidden');

                // Закрыть все другие меню
                document.querySelectorAll('[data-dropdown-menu]').forEach(function(otherMenu) {
                    if (otherMenu !== menu) {
                        otherMenu.classList.add('hidden');
                    }
                });
                document.querySelectorAll('[data-dropdown-toggle]').forEach(function(otherToggle) {
                    if (otherToggle !== toggle) {
                        otherToggle.classList.remove('bg-slate-100');
                    }
                });

                // Явное открытие/закрытие (без «двойного» переключения)
                menu.classList.toggle('hidden', !willOpen);
                toggle.classList.toggle('bg-slate-100', willOpen);

                if (!willOpen) {
                    return;
                }

                positionDropdownMenu(menu, toggle);
            });
        });
    }

    // Меню лежит в карточке активных заявок с overflow-x-auto: при
    // position: absolute контейнер обрезает его, и оно выглядит съехавшим
    // и обрубленным. Крепим меню к окну через fixed — оно не подчиняется
    // overflow предков. Тот же подход, что и в разделе «Все заявки».
    function positionDropdownMenu(menu, toggle) {
        const gap = 8;

        menu.style.position = 'fixed';
        menu.style.zIndex = '1000';
        menu.style.boxShadow = '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)';
        menu.style.maxHeight = '80vh';
        menu.style.overflowY = 'auto';
        menu.style.right = 'auto';
        menu.style.bottom = 'auto';

        const rect = toggle.getBoundingClientRect();
        const menuWidth = menu.offsetWidth || 192;
        const menuHeight = menu.offsetHeight;

        // По горизонтали прижимаем правый край меню к кнопке и следим,
        // чтобы оно не вылезло за края окна.
        let left = rect.right - menuWidth;
        left = Math.max(gap, Math.min(left, window.innerWidth - menuWidth - gap));
        menu.style.left = left + 'px';

        // Если снизу не хватает места — открываем вверх.
        const spaceBelow = window.innerHeight - rect.bottom;
        menu.style.top = (spaceBelow < menuHeight + gap && rect.top > menuHeight + gap)
            ? (rect.top - menuHeight - gap) + 'px'
            : (rect.bottom + gap) + 'px';
    }

    // Меню закреплено за окном — при прокрутке или изменении размера оно бы
    // «отклеилось» от своей кнопки, поэтому просто закрываем его.
    function closeAllDropdownMenus() {
        document.querySelectorAll('[data-dropdown-menu]').forEach(function(menu) {
            menu.classList.add('hidden');
        });
        document.querySelectorAll('[data-dropdown-toggle]').forEach(function(toggle) {
            toggle.classList.remove('bg-slate-100');
        });
    }

    window.addEventListener('scroll', closeAllDropdownMenus, true);
    window.addEventListener('resize', closeAllDropdownMenus);

    // Инициализируем панель техника после загрузки DOM
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('tech-tickets-container')) {
            initTechnicianDashboard();
        }

        // Инициализируем выпадающие меню
        initTableDropdowns();

        // Закрытие меню при клике вне его
        document.addEventListener('click', function(e) {
            if (!e.target.closest('[data-dropdown]')) {
                document.querySelectorAll('[data-dropdown-menu]').forEach(function(menu) {
                    menu.classList.add('hidden');
                });
                document.querySelectorAll('[data-dropdown-toggle]').forEach(function(toggle) {
                    toggle.classList.remove('bg-slate-100');
                });
            }
        });
    });
    </script>
    @endpush
    @endif

</div>
@endsection
