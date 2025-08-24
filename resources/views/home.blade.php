@extends('layouts.app')

@section('title', 'Главная - ICT')

@section('content')
<div class="space-y-16">
    <!-- Hero Section -->
    <section class="relative bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900 overflow-hidden">
        <div class="absolute inset-0 bg-black/10"></div>
        <div class="relative max-w-6xl mx-auto px-6 py-20">
            <div class="text-center text-white">
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold mb-8 leading-tight">
                    Служба технической поддержки
                </h1>
                <p class="text-xl sm:text-2xl text-slate-200 mb-10 max-w-3xl mx-auto leading-relaxed">
                    Мы здесь, чтобы помочь вам решить любые технические вопросы быстро и эффективно
                </p>

                @guest
                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    <a href="{{ route('login') }}"
                        class="inline-flex items-center justify-center px-8 py-4 bg-white text-slate-900 font-semibold rounded-lg hover:bg-slate-100 transition-colors duration-200">
                        Войти в систему
                    </a>
                    <a href="{{ route('register') }}"
                        class="inline-flex items-center justify-center px-8 py-4 border border-white text-white font-semibold rounded-lg hover:bg-white hover:text-slate-900 transition-colors duration-200">
                        Зарегистрироваться
                    </a>
                </div>
                @endguest
            </div>

            @auth
            <!-- Quick Actions Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-20">
                <a href="{{ route('tickets.create') }}"
                    class="group bg-white/5 backdrop-blur-sm rounded-xl p-6 text-white hover:bg-white/10 transition-all duration-200">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold mb-2">Подать заявку</h3>
                            <p class="text-slate-300 text-sm leading-relaxed">Создайте новую заявку в службу поддержки</p>
                        </div>
                    </div>
                </a>

                @if(auth()->user()->hasRole(['admin', 'master', 'technician']))
                <a href="{{ route('knowledge.index') }}"
                    class="group bg-white/5 backdrop-blur-sm rounded-xl p-6 text-white hover:bg-white/10 transition-all duration-200">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-emerald-500 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                                <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold mb-2">База знаний</h3>
                            <p class="text-slate-300 text-sm leading-relaxed">Найдите ответы на часто задаваемые вопросы</p>
                        </div>
                    </div>
                </a>
                @endif

                @if(user_can_manage_equipment())
                <a href="{{ route('equipment.index') }}"
                    class="group bg-white/5 backdrop-blur-sm rounded-xl p-6 text-white hover:bg-white/10 transition-all duration-200">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-violet-500 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold mb-2">Управление оборудованием</h3>
                            <p class="text-slate-300 text-sm leading-relaxed">Просмотр и управление техническим оборудованием</p>
                        </div>
                    </div>
                </a>
                @elseif(user_is_technician())
                <a href="{{ route('all-tickets.index') }}"
                    class="group bg-white/5 backdrop-blur-sm rounded-xl p-6 text-white hover:bg-white/10 transition-all duration-200">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-violet-500 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 12l2 2 4-4"></path>
                                <path d="M21 12c-1 0-2-1-2-2s1-2 2-2 2 1 2 2-1 2-2 2z"></path>
                                <path d="M3 12c1 0 2-1 2-2s-1-2-2-2-2 1-2 2 1 2 2 2z"></path>
                                <path d="M12 3c0 1-1 2-2 2s-2 1-2 2 1 2 2 2 2 1 2 2 1-2 2-2 2-1 2-2-1-2-2-2-2-1-2-2z"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold mb-2">Все заявки</h3>
                            <p class="text-slate-300 text-sm leading-relaxed">Управление всеми заявками в системе</p>
                        </div>
                    </div>
                </a>
                @else
                <a href="{{ route('tickets.index') }}"
                    class="group bg-white/5 backdrop-blur-sm rounded-xl p-6 text-white hover:bg-white/10 transition-all duration-200">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-violet-500 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 18v-6a9 9 0 0 1 18 0v6"></path>
                                <path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold mb-2">Мои заявки</h3>
                            <p class="text-slate-300 text-sm leading-relaxed">Отследите статус ваших обращений</p>
                        </div>
                    </div>
                </a>
                @endif
            </div>
            @endauth
        </div>
    </section>

    <!-- Tickets Dashboard for Technicians -->
    @if(user_can_manage_tickets())
    <section class="max-w-6xl mx-auto px-6 py-8">
        <div class="bg-white rounded-2xl shadow-md border border-slate-200 p-8">
            <!-- Header -->
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-8 gap-4 md:gap-0">
                <div>
                    <h2 class="text-3xl font-bold text-slate-900 mb-2">Активные заявки</h2>
                    <p class="text-slate-600">Заявки, требующие вашего внимания</p>
                </div>
                <div class="flex items-center gap-4 flex-wrap">
                    <div class="flex items-center gap-2 text-sm text-slate-600">
                        <div class="w-2 h-2 bg-green-500 rounded-full" id="tech-status-indicator"></div>
                        <span id="tech-last-updated">Загружено</span>
                    </div>
                    <button id="tech-refresh-btn" class="btn-secondary btn-sm flex items-center gap-2 hover:bg-slate-100 transition duration-200">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="23,4 23,10 17,10"></polyline>
                            <path d="M20.49,15a9,9,0,1,1-2.12-9.36L23,10"></path>
                        </svg>
                        Обновить
                    </button>
                    <a href="{{ route('all-tickets.index') }}" class="btn-primary btn-sm">
                        Все заявки
                    </a>
                    <button id="test-api-btn" class="btn-secondary btn-sm flex items-center gap-2 hover:bg-slate-100 transition duration-200">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 12l2 2 4-4"></path>
                            <circle cx="12" cy="12" r="9"></circle>
                        </svg>
                        Тест API
                    </button>
                </div>
            </div>

            <!-- Stats -->
            @if(isset($ticketStats))
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
                <div class="bg-slate-50 rounded-lg p-4 text-center shadow-sm hover:shadow-md transition-shadow duration-200">
                    <div class="text-2xl font-bold text-slate-900 mb-1 uppercase tracking-wide" id="tech-total-count">{{ $ticketStats['total'] }}</div>
                    <div class="text-sm text-slate-600">Всего</div>
                </div>
                <div class="bg-blue-50 rounded-lg p-4 text-center shadow-sm hover:shadow-md transition-shadow duration-200">
                    <div class="text-2xl font-bold text-blue-600 mb-1 uppercase tracking-wide" id="tech-open-count">{{ $ticketStats['open'] }}</div>
                    <div class="text-sm text-slate-600">Открытые</div>
                </div>
                <div class="bg-yellow-50 rounded-lg p-4 text-center shadow-sm hover:shadow-md transition-shadow duration-200">
                    <div class="text-2xl font-bold text-yellow-600 mb-1 uppercase tracking-wide" id="tech-progress-count">{{ $ticketStats['in_progress'] }}</div>
                    <div class="text-sm text-slate-600">В работе</div>
                </div>
                <div class="bg-green-50 rounded-lg p-4 text-center shadow-sm hover:shadow-md transition-shadow duration-200">
                    <div class="text-2xl font-bold text-green-600 mb-1 uppercase tracking-wide" id="tech-resolved-count">{{ $ticketStats['resolved'] }}</div>
                    <div class="text-sm text-slate-600">Решённые</div>
                </div>
                <div class="bg-slate-50 rounded-lg p-4 text-center shadow-sm hover:shadow-md transition-shadow duration-200">
                    <div class="text-2xl font-bold text-slate-600 mb-1 uppercase tracking-wide" id="tech-closed-count">{{ $ticketStats['closed'] }}</div>
                    <div class="text-sm text-slate-600">Закрытые</div>
                </div>
            </div>
            @endif

            <!-- Tickets Table -->
            <div class="overflow-x-auto rounded-lg border border-slate-200">
                <table class="min-w-[700px] w-full">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-900 uppercase tracking-wide">Заявка</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-900 uppercase tracking-wide">Статус</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-900 uppercase tracking-wide">Приоритет</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-900 uppercase tracking-wide">Заявитель</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-900 uppercase tracking-wide">Дата</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-900 uppercase tracking-wide">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200" id="tech-tickets-tbody">
                        @if(isset($tickets) && $tickets->count() > 0)
                            @foreach($tickets->take(10) as $ticket)
                            <tr class="hover:bg-slate-50 transition-colors duration-200">
                                <td class="px-4 py-3">
                                    <a href="{{ route('tickets.show', $ticket) }}" class="text-slate-900 font-medium hover:text-blue-600 transition-colors duration-200">
                                        {{ Str::limit($ticket->title, 40) }}
                                    </a>
                                    @if($ticket->room)
                                        <div class="text-xs text-slate-500 mt-1">🏢 {{ $ticket->room->number }} - {{ $ticket->room->name ?? $ticket->room->type_name }}</div>
                                    @elseif($ticket->location)
                                        <div class="text-xs text-slate-500 mt-1">📍 {{ $ticket->location->name }}</div>
                                    @endif
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
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $statusColors[$ticket->status] ?? 'bg-slate-100 text-slate-800' }}">
                                        {{ $statusLabels[$ticket->status] ?? $ticket->status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $ticket->priority == 'urgent' ? 'bg-red-200 text-red-900' : get_priority_badge_class($ticket->priority) }}">
                                        {{ $ticket->priority == 'urgent' ? 'Срочный' : format_ticket_priority($ticket->priority) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm text-slate-900">{{ $ticket->reporter_name ?: '—' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm text-slate-600">{{ $ticket->created_at->format('d.m H:i') }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('tickets.show', $ticket) }}" class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                                            Открыть
                                        </a>
                                        @if($ticket->status === 'open')
                                            <form action="{{ route('tickets.start', $ticket) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="text-green-600 hover:text-green-700 font-medium text-sm ml-2">
                                                    В работу
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-slate-400 mb-4 animate-pulse" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2 2m16-7H4m16 0l-2-2m2 2l-2 2M4 13l2-2m-2 2l2 2" />
                                        </svg>
                                        <p class="text-lg font-medium mb-1">Пока нет заявок</p>
                                        <p class="text-sm">Новые заявки будут появляться здесь автоматически</p>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </section>
    @endif


    <!-- Features Section -->
    <section class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-16">
            <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-4">Почему выбирают нас?</h2>
            <p class="text-lg text-slate-600 max-w-2xl mx-auto">Мы предоставляем качественную техническую поддержку с использованием современных технологий</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="mx-auto w-16 h-16 bg-blue-500 rounded-xl flex items-center justify-center mb-6">
                    <svg class="w-8 h-8 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M12 6v6l4 2"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-slate-900 mb-3">Быстрый отклик</h3>
                <p class="text-slate-600">Мы стараемся отвечать на заявки как можно быстрее</p>
            </div>

            <div class="text-center">
                <div class="mx-auto w-16 h-16 bg-emerald-500 rounded-xl flex items-center justify-center mb-6">
                    <svg class="w-8 h-8 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 12l2 2 4-4"></path>
                        <path d="M21 12c-1 0-2-1-2-2s1-2 2-2 2 1 2 2-1 2-2 2z"></path>
                        <path d="M3 12c1 0 2-1 2-2s-1-2-2-2-2 1-2 2 1 2 2 2z"></path>
                        <path d="M12 3c0 1-1 2-2 2s-2 1-2 2 1 2 2 2 2 1 2 2 1-2 2-2 2-1 2-2-1-2-2-2-2-1-2-2z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-slate-900 mb-3">Экспертная поддержка</h3>
                <p class="text-slate-600">Наши специалисты имеют большой опыт решения технических проблем</p>
            </div>

            <div class="text-center">
                <div class="mx-auto w-16 h-16 bg-violet-500 rounded-xl flex items-center justify-center mb-6">
                    <svg class="w-8 h-8 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-slate-900 mb-3">Высокое качество</h3>
                <p class="text-slate-600">Мы гарантируем качественное решение всех технических вопросов</p>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    @auth
    @unless (user_has_role('user'))

    <section class="max-w-6xl mx-auto px-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-8">
                <div class="mb-6 lg:mb-0">
                    <h2 class="text-3xl font-bold text-slate-900 mb-2">Часто возникающие проблемы</h2>
                    <p class="text-slate-600">Найдите быстрые ответы на популярные вопросы</p>
                </div>
            </div>

            @if(isset($faqs) && $faqs->count())
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($faqs as $faq)
                <article class="group border border-slate-200 rounded-xl p-6 hover:shadow-md hover:border-slate-300 transition-all duration-200">
                    <a href="{{ route('homepage-faq.show', $faq->slug) }}" class="block">
                        <h3 class="text-lg font-semibold text-slate-900 mb-3 group-hover:text-blue-600 transition-colors duration-200 line-clamp-2">
                            {{ $faq->title }}
                        </h3>
                        <p class="text-slate-600 text-sm leading-relaxed mb-4 line-clamp-3">
                            {{ $faq->excerpt ?? Str::limit(strip_tags($faq->content), 120) }}
                        </p>
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-slate-500">
                                {{ $faq->created_at ? $faq->created_at->format('d.m.Y') : '—' }}
                            </div>
                            <div class="text-blue-600 group-hover:text-blue-700 transition-colors duration-200">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                    <polyline points="12 5 19 12 12 19"></polyline>
                                </svg>
                            </div>
                        </div>
                    </a>
                </article>
                @endforeach
            </div>
            @else
            <div class="text-center py-12">
                <div class="mx-auto w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                        <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-slate-900 mb-2">Пока нет часто задаваемых вопросов</h3>
                <p class="text-slate-600">Мы работаем над наполнением базы знаний</p>
            </div>
            @endif
        </div>
    </section>
    @endunless
    @endauth

    @if(user_can_manage_tickets())
    @push('scripts')
    <script>
    console.log('=== HOME.BLADE.PHP SCRIPT LOADING ===');
    const canManageTickets = @json(user_can_manage_tickets());
    const csrfToken = '{{ csrf_token() }}';

    console.log('canManageTickets:', canManageTickets);
    console.log('csrfToken:', csrfToken);

    // Объявляем переменные в глобальной области видимости
    let techRefreshBtn, techStatusIndicator, techLastUpdated, techTicketsContainer;
    let techRefreshInterval;
    const TECH_REFRESH_INTERVAL = 15000; // 15 секунд

    // Функция инициализации панели техника
    function initTechnicianDashboard() {
        console.log('=== DOM CONTENT LOADED ===');
        techRefreshBtn = document.getElementById('tech-refresh-btn');
        techStatusIndicator = document.getElementById('tech-status-indicator');
        techLastUpdated = document.getElementById('tech-last-updated');
        techTicketsContainer = document.getElementById('tech-tickets-container');

        console.log('Elements found:');
        console.log('- techRefreshBtn:', techRefreshBtn);
        console.log('- techStatusIndicator:', techStatusIndicator);
        console.log('- techLastUpdated:', techLastUpdated);
        console.log('- techTicketsContainer:', techTicketsContainer);

        async function refreshTechTickets() {
            try {
                console.log('Начинаем обновление заявок...');
                techStatusIndicator.className = 'w-2 h-2 bg-yellow-500 rounded-full';

                const response = await fetch('{{ route("home.technician.tickets") }}');
                console.log('Получен ответ от API:', response.status);

                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

                const data = await response.json();
                console.log('Получены данные:', data);

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

                    console.log('Статистика обновлена:', data.stats);
                }

                if (data.tickets && Array.isArray(data.tickets)) {
                    console.log(`Получено ${data.tickets.length} заявок от API:`, data.tickets.map(t => t.title));
                    updateTechTicketsTable(data.tickets.slice(0, 10));
                    console.log(`Обновлена таблица с ${data.tickets.length} заявками`);
                } else {
                    console.warn('Заявки не найдены в ответе API:', data);
                }

                if (techLastUpdated) techLastUpdated.textContent = `Обновлено: ${data.last_updated}`;
                techStatusIndicator.className = 'w-2 h-2 bg-green-500 rounded-full';
                console.log('Обновление завершено успешно');

            } catch (error) {
                console.error('Ошибка при обновлении заявок:', error);
                techStatusIndicator.className = 'w-2 h-2 bg-red-500 rounded-full';
                if (techLastUpdated) techLastUpdated.textContent = 'Ошибка обновления';

                if (error.message.includes('403') || error.message.includes('Unauthorized')) {
                    console.warn('Нет доступа к API заявок');
                } else {
                    console.error('Детали ошибки:', error.message);
                }
            }
        }

        function updateTechTicketsTable(tickets) {
            console.log('Обновление таблицы заявок, получено:', tickets.length, 'заявок');
            const tbody = document.getElementById('tech-tickets-tbody');
            if (!tbody) {
                console.warn('Элемент tech-tickets-tbody не найден');
                return;
            }

            try {
                // Полностью очищаем таблицу, создавая новый tbody
                const newTbody = document.createElement('tbody');
                newTbody.className = 'divide-y divide-slate-200';

                if (tickets.length === 0) {
                    const emptyRow = createEmptyRow();
                    newTbody.appendChild(emptyRow);
                    tbody.parentNode.replaceChild(newTbody, tbody);
                    return;
                }

                // Создаем новые строки
                tickets.forEach((ticket, index) => {
                    try {
                        const row = createTechTicketRowElement(ticket);
                        newTbody.appendChild(row);
                        console.log(`Добавлена новая строка ${index + 1}: ${ticket.title}`);
                    } catch (error) {
                        console.error(`Ошибка при создании строки для заявки ${ticket.id}:`, error, ticket);
                    }
                });

                // Заменяем старый tbody на новый
                tbody.parentNode.replaceChild(newTbody, tbody);
                console.log(`Таблица обновлена с ${tickets.length} строками`);
            } catch (error) {
                console.error('Ошибка при обновлении таблицы:', error);

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
            cell.colSpan = 6;
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
            cell.colSpan = 6;
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
            row.className = 'hover:bg-slate-50 transition-colors duration-200';
            row.setAttribute('data-ticket-id', ticket.id);

            // Ячейка с названием заявки
            const titleCell = document.createElement('td');
            titleCell.className = 'px-4 py-3';

            const titleDiv = document.createElement('div');

            const titleLink = document.createElement('a');
            titleLink.href = ticket.url || '#';
            titleLink.className = 'text-slate-900 font-medium hover:text-blue-600 transition-colors duration-200';
            titleLink.textContent = truncatedTitle;

            titleDiv.appendChild(titleLink);

            if (ticket.room && ticket.room.number) {
                const roomName = ticket.room.name || ticket.room.type_name || '';
                const roomInfo = document.createElement('div');
                roomInfo.className = 'text-xs text-slate-500';
                roomInfo.textContent = `🏢 ${ticket.room.number}${roomName ? ' - ' + roomName : ''}`;
                titleDiv.appendChild(roomInfo);
            } else if (ticket.location_name) {
                const locationInfo = document.createElement('div');
                locationInfo.className = 'text-xs text-slate-500';
                locationInfo.textContent = `📍 ${ticket.location_name}`;
                titleDiv.appendChild(locationInfo);
            }

            titleCell.appendChild(titleDiv);

            // Ячейка статуса
            const statusCell = document.createElement('td');
            statusCell.className = 'px-4 py-3';

            const statusSpan = document.createElement('span');
            statusSpan.className = `inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${statusColors[ticket.status] || 'bg-slate-100 text-slate-800'}`;
            statusSpan.textContent = statusLabels[ticket.status] || ticket.status;

            statusCell.appendChild(statusSpan);

            // Ячейка приоритета
            const priorityCell = document.createElement('td');
            priorityCell.className = 'px-4 py-3';

            const prioritySpan = document.createElement('span');
            if (ticket.priority === 'urgent') {
                prioritySpan.className = 'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-200 text-red-900';
                prioritySpan.textContent = 'Срочный';
            } else {
                prioritySpan.className = `inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${priorityColors[ticket.priority] || 'bg-slate-100 text-slate-800'}`;
                prioritySpan.textContent = priorityLabels[ticket.priority] || ticket.priority;
            }

            priorityCell.appendChild(prioritySpan);

            // Ячейка заявителя
            const reporterCell = document.createElement('td');
            reporterCell.className = 'px-4 py-3';

            const reporterDiv = document.createElement('div');
            reporterDiv.className = 'text-sm text-slate-900';
            reporterDiv.textContent = ticket.reporter_name || '—';

            reporterCell.appendChild(reporterDiv);

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

            const openLink = document.createElement('a');
            openLink.href = ticket.url || '#';
            openLink.className = 'text-blue-600 hover:text-blue-700 font-medium text-sm';
            openLink.textContent = 'Открыть';

            actionsDiv.appendChild(openLink);

            if (ticket.status === 'open') {
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
                startButton.className = 'text-green-600 hover:text-green-700 font-medium text-sm ml-2';
                startButton.textContent = 'В работу';

                startForm.appendChild(csrfInput);
                startForm.appendChild(startButton);
                actionsDiv.appendChild(startForm);
            }

            actionsCell.appendChild(actionsDiv);

            // Добавляем все ячейки в строку
            row.appendChild(titleCell);
            row.appendChild(statusCell);
            row.appendChild(priorityCell);
            row.appendChild(reporterCell);
            row.appendChild(dateCell);
            row.appendChild(actionsCell);

            return row;
        }

        // Start event listeners setup
        function setupEventListeners() {
            if (techRefreshBtn) {
            techRefreshBtn.addEventListener('click', function() {
                console.log('Нажата кнопка обновления заявок');
                refreshTechTickets();
            });
        } else {
            console.warn('Кнопка обновления не найдена');
        }

            const testApiBtn = document.getElementById('test-api-btn');
            if (testApiBtn) {
                testApiBtn.addEventListener('click', function() {
                    console.log('=== ТЕСТ API ===');
                console.log('URL API:', '{{ route("home.technician.tickets") }}');
                console.log('Пользователь может управлять заявками:', canManageTickets);
                console.log('CSRF токен:', csrfToken);

                fetch('{{ route("home.technician.tickets") }}')
                    .then(response => {
                        console.log('Статус ответа:', response.status, response.statusText);
                        console.log('Заголовки ответа:', [...response.headers]);
                        return response.json();
                    })
                    .then(data => {
                        console.log('=== ДАННЫЕ ОТ API ===');
                        console.log('Полный ответ:', data);
                        console.log('Количество заявок:', data.tickets ? data.tickets.length : 'не найдено');
                        console.log('Статистика:', data.stats);
                        console.log('Время обновления:', data.last_updated);
                        if (data.tickets && data.tickets.length > 0) console.log('Первая заявка:', data.tickets[0]);
                        alert('Результат теста выведен в консоль браузера (F12)');
                    })
                    .catch(error => {
                        console.error('=== ОШИБКА API ===');
                        console.error('Ошибка:', error);
                        alert('Ошибка: ' + error.message);
                    });
            });
            }
        } // End of setupEventListeners function

        function startTechAutoRefresh() {
            console.log('Запуск интервала автообновления...');
            techRefreshInterval = setInterval(() => {
                console.log('Автообновление: вызываем refreshTechTickets...');
                refreshTechTickets();
            }, TECH_REFRESH_INTERVAL);
        }

        function stopTechAutoRefresh() {
            if (techRefreshInterval) {
                console.log('Остановка автообновления...');
                clearInterval(techRefreshInterval);
                techRefreshInterval = null;
            }
        }

        console.log('Запуск автообновления заявок каждые', TECH_REFRESH_INTERVAL / 1000, 'секунд');
        console.log('Выполняем первоначальное обновление заявок');

        if (canManageTickets) {
            console.log('Пользователь может управлять заявками, запускаем обновление...');
            refreshTechTickets();
            startTechAutoRefresh();
        } else {
            console.warn('Пользователь НЕ может управлять заявками, пропускаем автообновление');
        }

        window.addEventListener('beforeunload', stopTechAutoRefresh);

        if (techLastUpdated) {
            techLastUpdated.textContent = `Загружено: ${new Date().toLocaleString('ru-RU')}`;
        }

        document.addEventListener('visibilitychange', function() {
            if (!document.hidden && canManageTickets) {
                console.log('Страница стала видимой, обновляем заявки...');
                refreshTechTickets();
            }
        });

        // Initialize event listeners
        setupEventListeners();
    }

    // Инициализируем панель техника после загрузки DOM
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM полностью загружен, инициализируем техническую панель');
        if (document.getElementById('tech-tickets-container')) {
            initTechnicianDashboard();
        }
    });
    </script>
    @endpush
    @endif

    <!-- CTA Section -->
    @guest
    <section class="max-w-6xl mx-auto px-6">
        <div class="bg-slate-900 rounded-2xl p-8 text-center text-white">
            <h2 class="text-3xl font-bold mb-4">Готовы начать?</h2>
            <p class="text-lg text-slate-300 mb-8 max-w-2xl mx-auto">
                Присоединяйтесь к нашей системе технической поддержки и получите быструю помощь
            </p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="{{ route('register') }}"
                    class="inline-flex items-center justify-center px-8 py-4 bg-white text-slate-900 font-semibold rounded-lg hover:bg-slate-100 transition-colors duration-200">
                    Создать аккаунт
                </a>
                <a href="{{ route('login') }}"
                    class="inline-flex items-center justify-center px-8 py-4 border border-white text-white font-semibold rounded-lg hover:bg-white hover:text-slate-900 transition-colors duration-200">
                    Войти в систему
                </a>
            </div>
        </div>
    </section>
    @endguest
</div>
@endsection
