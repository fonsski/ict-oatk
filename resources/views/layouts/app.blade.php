<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ICT Help')</title>
    @vite(['resources/css/app.css', 'resources/css/layout.css', 'resources/js/app.js', 'resources/js/layout.js'])
</head>

@php
    // Пункты меню собираются один раз: тот же список служит боковой панелью
    // на компьютере и выезжающим меню на телефоне. Права проверяются теми же
    // условиями, что и раньше.
    $isStaff = auth()->check() && auth()->user()->hasRole(['admin', 'master', 'technician']);

    $navGroups = [];

    if (auth()->check()) {
        $work = [
            ['Главная', 'home', request()->routeIs('home')],
            ['Мои заявки', 'tickets.index', request()->routeIs('tickets.index')],
        ];
        if (user_can_manage_tickets()) {
            $work[] = ['Все заявки', 'all-tickets.index', request()->routeIs('all-tickets.*')];
        }
        if ($isStaff) {
            $work[] = ['Календарь', 'calendar.index', request()->routeIs('calendar.*')];
        }
        $navGroups['Работа'] = $work;

        // Разделы, куда пускают технику: оборудование, расходники, закупки,
        // документы, знания и справочники. Кабинеты и пользователи остаются
        // за администратором и мастером.
        if ($isStaff) {
            $navGroups['Учёт'] = [
                ['Оборудование', 'equipment.index', request()->routeIs('equipment.*') && !request()->routeIs('equipment.equipment-categories.*')],
                ['Расходники', 'consumables.index', request()->routeIs('consumables.*') || request()->routeIs('consumable-write-offs.*')],
                ['Закупки', 'purchases.index', request()->routeIs('purchases.*')],
                ['Заявки ТМЦ', 'tmc-requests.index', request()->routeIs('tmc-requests.*')],
                ['Документы', 'documents.index', request()->routeIs('documents.*') || request()->routeIs('document-types.*')],
                ['Топология', 'topology.index', request()->routeIs('topology.*')],
            ];

            $knowledge = [
                ['База знаний', 'knowledge.index', request()->routeIs('knowledge.index')],
                ['Категории знаний', 'knowledge.categories.index', request()->routeIs('knowledge.categories.*')],
            ];
            if (user_can_manage_faq()) {
                $knowledge[] = ['FAQ главной', 'homepage-faq.index', request()->routeIs('homepage-faq.*')];
            }
            $knowledge[] = ['Операционные системы', 'operating-systems.index', request()->routeIs('operating-systems.*')];
            $knowledge[] = ['Категории оборудования', 'equipment.equipment-categories.index', request()->routeIs('equipment.equipment-categories.*')];
            $navGroups['Знания и справочники'] = $knowledge;
        }

        if (user_can_manage_users()) {
            $navGroups['Управление'] = [
                ['Кабинеты', 'room.index', request()->routeIs('room.*')],
                ['Пользователи', 'user.index', request()->routeIs('user.*')],
            ];
        }
    }

    // Раздел и пункт для строки пути в верхней панели.
    $crumbGroup = null;
    $crumbItem = null;
    foreach ($navGroups as $groupName => $items) {
        foreach ($items as $item) {
            if ($item[2]) {
                $crumbGroup = $groupName;
                $crumbItem = $item[0];
                break 2;
            }
        }
    }
@endphp

<body class="app-body">
    <a href="#main" class="skip-link">К содержимому</a>

    <div class="frame {{ auth()->check() ? '' : 'frame--guest' }}">
        @auth
            <!-- Боковая панель: на телефоне выезжает по кнопке меню -->
            <aside id="mobile-menu" class="sidebar" aria-label="Основная навигация">
                <a href="{{ route('home') }}" class="brand">
                    <svg class="brand-mark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"
                        role="presentation">
                        <rect x="3" y="4" width="18" height="12" rx="2"></rect>
                        <path d="M8 20h8M12 16v4"></path>
                        <path d="m8.5 10 2.5 2.5 4.5-5"></path>
                    </svg>
                    <span class="brand-name">ICT Help</span>
                </a>

                <nav class="nav" aria-label="Разделы">
                    @foreach ($navGroups as $groupName => $items)
                        <div class="nav-group">
                            <div class="nav-group-label">{{ $groupName }}</div>
                            @foreach ($items as [$label, $routeName, $active])
                                <a href="{{ route($routeName) }}" class="nav-item"
                                    @if ($active) aria-current="page" @endif>{{ $label }}</a>
                            @endforeach
                        </div>
                    @endforeach
                </nav>

                <div class="sidebar-foot">
                    <div>Служба поддержки колледжа</div>
                    <a href="tel:+79503362989">+7 (950) 336-29-89</a>
                    <div>Пн–Пт 8:30–17:00, обед 12:30–13:00</div>
                </div>
            </aside>
            <div id="sidebar-backdrop" class="sidebar-backdrop"></div>
        @endauth

        <!-- Верхняя панель -->
        <header class="topbar">
            @auth
                <button type="button" id="mobile-menu-button" class="topbar-menu" aria-label="Открыть меню"
                    aria-controls="mobile-menu" aria-expanded="false">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"
                        stroke-linecap="round" role="presentation">
                        <path d="M4 7h16M4 12h16M4 17h16" />
                    </svg>
                </button>
                <a href="{{ route('home') }}" class="brand brand--compact">
                    <svg class="brand-mark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"
                        role="presentation">
                        <rect x="3" y="4" width="18" height="12" rx="2"></rect>
                        <path d="M8 20h8M12 16v4"></path>
                        <path d="m8.5 10 2.5 2.5 4.5-5"></path>
                    </svg>
                    <span class="brand-name">ICT Help</span>
                </a>
                @if ($crumbItem)
                    <div class="crumbs" aria-label="Где вы находитесь">
                        <span class="crumbs-parent">{{ $crumbGroup }}</span>
                        <span class="crumbs-sep"></span>
                        <span class="crumbs-current">{{ $crumbItem }}</span>
                    </div>
                @endif
            @else
                <a href="{{ route('home') }}" class="brand">
                    <svg class="brand-mark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"
                        role="presentation">
                        <rect x="3" y="4" width="18" height="12" rx="2"></rect>
                        <path d="M8 20h8M12 16v4"></path>
                        <path d="m8.5 10 2.5 2.5 4.5-5"></path>
                    </svg>
                    <span class="brand-name">ICT Help</span>
                </a>
            @endauth

            <div class="topbar-actions" role="navigation" aria-label="Действия">
                @auth
                    <a href="{{ route('tickets.create') }}" class="btn-primary btn-sm topbar-cta">Подать заявку</a>

                    @if (user_can_manage_tickets())
                        <div class="relative">
                            <button type="button" id="notifications-menu-button" class="icon-button"
                                aria-label="Уведомления" aria-haspopup="true">
                                <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"
                                    stroke-linejoin="round" role="presentation">
                                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                    <path d="m13.73 21a2 2 0 0 1-3.46 0"></path>
                                </svg>
                                <span id="notification-badge" class="hidden notif-badge">0</span>
                            </button>

                            <div id="notifications-dropdown"
                                class="hidden absolute right-0 mt-2 w-80 max-w-[calc(100vw-2rem)] bg-white rounded-lg shadow-lg border border-gray-200 z-[100]">
                                <div class="py-2">
                                    <div class="px-4 py-2 border-b border-gray-200 flex items-center justify-between gap-3">
                                        <h3 class="text-sm font-semibold text-gray-900">Уведомления</h3>
                                        <button id="mark-all-read" class="text-xs text-blue-600 hover:text-blue-700">Отметить
                                            все как прочитанные</button>
                                    </div>
                                    <div id="notifications-list" class="max-h-64 overflow-y-auto">
                                        <div class="p-4 text-center text-gray-500 text-sm">Загрузка...</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="relative">
                        <button type="button" id="user-menu-button" class="user-button" aria-expanded="false"
                            aria-haspopup="true" aria-label="Меню пользователя">
                            <span class="user-name">{{ auth()->user()->name }}</span>
                            <svg width="16" height="16" class="shrink-0" xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"
                                stroke-linecap="round" stroke-linejoin="round" role="presentation">
                                <polyline points="6,9 12,15 18,9"></polyline>
                            </svg>
                        </button>

                        <div id="user-dropdown"
                            class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-[100]">
                            <div class="py-1">
                                <a href="{{ route('settings.index') }}"
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Настройки</a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Выход</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Гость тоже видит свои обращения: они закреплены
                         за ним долгоживущей cookie. --}}
                    <a href="{{ route('tickets.index') }}"
                        class="topbar-link {{ request()->routeIs('tickets.index') ? 'is-active' : '' }}">Мои заявки</a>
                    <a href="{{ route('login') }}" class="topbar-link">Войти</a>
                    <a href="{{ route('tickets.create') }}" class="btn-primary btn-sm topbar-cta">Подать заявку</a>
                @endauth
            </div>
        </header>

        <!-- Контейнер уведомлений -->
        <div id="notifications-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

        <!-- Лист с содержимым страницы -->
        <main id="main" class="sheet">
            @yield('content')

            @guest
                <footer class="sheet-foot">
                    <span>Служба поддержки колледжа</span>
                    <a href="tel:+79503362989">+7 (950) 336-29-89</a>
                    <span>Пн–Пт 8:30–17:00, обед 12:30–13:00</span>
                </footer>
            @endguest
        </main>
    </div>

    <script>
        // Переменные для маршрутов API
        window.routes = {
            'api.notifications.unread-count': '{{ route('api.notifications.unread-count') }}',
            'api.notifications.poll': '{{ route('api.notifications.poll') }}',
            'api.notifications.index': '{{ route('api.notifications.index') }}',
            'api.notifications.mark-as-read': '{{ route('api.notifications.mark-as-read', ['notificationId' => '__ID__']) }}',
            'api.notifications.mark-all-as-read': '{{ route('api.notifications.mark-all-as-read') }}'
        };
    </script>


    {{-- Реальное время (Reverb/Echo) и поллинг — только для авторизованных:
         приватные каналы требуют аутентификации. --}}
    @auth
    <script>
        window.authUserId = {{ auth()->id() }};
    </script>
    @vite(['resources/js/echo.js', 'resources/js/realtime.js', 'resources/js/live-updates.js'])
    @endauth

    @stack('scripts')
</body>

</html>
