<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ICT')</title>
    @vite(['resources/css/app.css', 'resources/css/layout.css', 'resources/js/app.js', 'resources/js/layout.js'])
</head>

<body class="min-h-screen bg-gray-50">
    <!-- Шапка -->
    <header class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Лого -->
                <a href="{{ route('home') }}" class="flex items-center space-x-2">
                    <div
                        class="w-8 h-8 bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M3 18v-6a9 9 0 0 1 18 0v6"></path>
                            <path
                                d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z">
                            </path>
                        </svg>
                    </div>
                    <span class="text-xl font-bold text-gray-900">ICT</span>
                </a>

                <!-- Навигация -->
                <nav class="hidden md:flex items-center space-x-6" role="navigation" aria-label="Основная навигация">
                    @auth
                        <a href="{{ route('home') }}"
                            class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('home') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' }}">
                            Главная
                        </a>

                        <a href="{{ route('tickets.create') }}"
                            class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('ticket.create') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' }}">
                            Подать заявку
                        </a>

                        <a href="{{ route('tickets.index') }}"
                            class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('tickets.index') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' }}">
                            Мои заявки
                        </a>

                        @if (user_can_manage_tickets())
                            <a href="{{ route('all-tickets.index') }}"
                                class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('all-tickets.*') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' }}">
                                Все заявки
                            </a>
                        @endif

                        @if (auth()->check() &&
                                auth()->user()->hasRole(['admin', 'master', 'technician']))
                            <a href="{{ route('knowledge.index') }}"
                                class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('knowledge.index') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' }}">
                                База знаний
                            </a>
                        @endif

                        @if (user_can_manage_users())
                            <!-- Админ меню -->
                            <div class="relative">
                                <button type="button" id="admin-menu-button"
                                    class="flex items-center space-x-1 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs(['equipment.*', 'room.*', 'user.*', 'homepage-faq.*']) ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' }}">
                                    <span>Управление</span>
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <polyline points="6,9 12,15 18,9"></polyline>
                                    </svg>
                                </button>

                                <!-- Админ меню -->
                                <div id="admin-dropdown"
                                    class="hidden absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg border border-gray-200 z-[100]">
                                    <div class="py-1">
                                        <a href="{{ route('equipment.index') }}"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('equipment.*') ? 'bg-blue-50 text-blue-600' : '' }}">
                                            Оборудование
                                        </a>
                                        <a href="{{ route('room.index') }}"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('room.*') ? 'bg-blue-50 text-blue-600' : '' }}">
                                            Кабинеты
                                        </a>
                                        <a href="{{ route('user.index') }}"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('user.*') ? 'bg-blue-50 text-blue-600' : '' }}">
                                            Пользователи
                                        </a>
                                        <a href="{{ route('homepage-faq.index') }}"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('homepage-faq.*') ? 'bg-blue-50 text-blue-600' : '' }}">
                                            FAQ главной
                                        </a>
                                        <a href="{{ route('knowledge.categories.index') }}"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('knowledge.categories.*') ? 'bg-blue-50 text-blue-600' : '' }}">
                                            Категории знаний
                                        </a>
                                        <a href="{{ route('equipment.equipment-categories.index') }}"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('equipment.equipment-categories.*') ? 'bg-blue-50 text-blue-600' : '' }}">
                                            Категории оборудования
                                        </a>

                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Notifications Menu -->
                        @if (user_can_manage_tickets())
                            <div class="relative">
                                <button type="button" id="notifications-menu-button"
                                    class="relative p-2 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-md">
                                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                        <path d="m13.73 21a2 2 0 0 1-3.46 0"></path>
                                    </svg>
                                    <span id="notification-badge"
                                        class="hidden absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">0</span>
                                </button>

                                <!-- Notifications Dropdown -->
                                <div id="notifications-dropdown"
                                    class="hidden absolute right-0 mt-2 w-80 bg-white rounded-md shadow-lg border border-gray-200 z-[100]">
                                    <div class="py-2">
                                        <div class="px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                                            <h3 class="text-sm font-semibold text-gray-900">Уведомления</h3>
                                            <button id="mark-all-read"
                                                class="text-xs text-blue-600 hover:text-blue-700">Отметить все как
                                                прочитанные</button>
                                        </div>
                                        <div id="notifications-list" class="max-h-64 overflow-y-auto">
                                            <div class="p-4 text-center text-gray-500 text-sm">Загрузка...</div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- User Menu -->
                        <div class="relative">
                            <button type="button" id="user-menu-button"
                                class="flex items-center space-x-2 px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:text-blue-600 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                aria-expanded="false" aria-haspopup="true" aria-label="Меню пользователя">
                                <span>{{ auth()->user()->name }}</span>
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="6,9 12,15 18,9"></polyline>
                                </svg>
                            </button>

                            <!-- Dropdown Menu -->
                            <div id="user-dropdown"
                                class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg border border-gray-200 z-[100]">
                                <div class="py-1">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit"
                                            class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            Выход
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="flex items-center space-x-4">
                            <!-- Войти -->
                            <a href="{{ route('login') }}"
                                class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-blue-600">
                                Войти
                            </a>

                            <!-- Регистрация -->
                            <a href="{{ route('register') }}"
                                class="px-4 py-2 text-sm font-medium bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                Регистрация
                            </a>
                        </div>
                    @endauth
                </nav>

                <!-- Кнопка мобильного меню -->
                <div class="md:hidden">
                    <button type="button" id="mobile-menu-button" class="text-gray-500 hover:text-gray-600 p-2">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation -->
        <div id="mobile-menu" class="hidden md:hidden bg-white border-t border-gray-200 mobile-menu"
            role="navigation" aria-label="Мобильная навигация">
            <div class="px-2 pt-2 pb-3 space-y-1">
                @auth
                    <!-- Главная -->
                    <a href="{{ route('home') }}"
                        class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('home') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' }}">
                        Главная
                    </a>

                    <!-- Подать заявку -->
                    <a href="{{ route('tickets.create') }}"
                        class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('ticket.create') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' }}">
                        Подать заявку
                    </a>

                    <!-- Мои заявки -->
                    <a href="{{ route('tickets.index') }}"
                        class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('tickets.index') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' }}">
                        Мои заявки
                    </a>

                    @if (user_can_manage_tickets())
                        <!-- Все заявки -->
                        <a href="{{ route('all-tickets.index') }}"
                            class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('all-tickets.*') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' }}">
                            Все заявки
                        </a>
                    @endif

                    @if (auth()->check() &&
                            auth()->user()->hasRole(['admin', 'master', 'technician']))
                        <!-- База знаний -->
                        <a href="{{ route('knowledge.index') }}"
                            class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('knowledge.index') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' }}">
                            База знаний
                        </a>
                    @endif

                    @if (user_can_manage_users())
                        <!-- Управление пользователями -->
                        <div class="border-t border-gray-200 pt-2 mt-2">
                            <div class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Управление
                            </div>
                            <a href="{{ route('equipment.index') }}"
                                class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('equipment.*') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' }}">
                                Оборудование
                            </a>

                            <a href="{{ route('room.index') }}"
                                class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('room.*') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' }}">
                                Кабинеты
                            </a>

                            <a href="{{ route('user.index') }}"
                                class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('user.*') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' }}">
                                Пользователи
                            </a>

                            <a href="{{ route('homepage-faq.index') }}"
                                class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('homepage-faq.*') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' }}">
                                FAQ главной
                            </a>
                            <a href="{{ route('equipment.equipment-categories.index') }}"
                                class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('equipment.equipment-categories.*') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' }}">
                                Категории оборудования
                            </a>
                        </div>
                    @endif

                    <!-- Меню пользователя для мобильных устройств -->
                    <div class="border-t border-gray-200 pt-4 mt-4">
                        <div class="flex items-center space-x-3 px-3 py-2">
                            <span class="text-base font-medium text-gray-900">{{ auth()->user()->name }}</span>
                        </div>

                        <form method="POST" action="{{ route('logout') }}" class="mt-2">
                            @csrf
                            <button type="submit"
                                class="w-full text-left px-3 py-2 text-base font-medium text-gray-600 hover:text-blue-600 hover:bg-blue-50">
                                Выход
                            </button>
                        </form>
                    </div>
                @else
                    <div class="space-y-2">
                        <a href="{{ route('login') }}"
                            class="block px-3 py-2 text-base font-medium text-gray-600 hover:text-blue-600">
                            Войти
                        </a>

                        <a href="{{ route('register') }}"
                            class="block px-3 py-2 text-base font-medium bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Регистрация
                        </a>
                    </div>
                @endauth
            </div>
        </div>
    </header>

    <!-- Контейнер уведомлений -->
    <div id="notifications-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

    <!-- Основной контент -->
    <main class="flex-1">
        @yield('content')
    </main>

    <!-- Футер -->
    <footer class="bg-gray-50 border-t border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Лого и описание -->
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center space-x-2 mb-4">
                        <div
                            class="w-8 h-8 bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M3 18v-6a9 9 0 0 1 18 0v6"></path>
                                <path
                                    d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z">
                                </path>
                            </svg>
                        </div>
                        <span class="text-xl font-bold text-gray-900">ICT</span>
                    </div>
                    <p class="text-gray-600 mb-4 max-w-md">
                        Современная система технической поддержки для колледжа.
                        Быстро, удобно, эффективно.
                    </p>
                </div>

                <!-- Контактная информация -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">
                        Контакты
                    </h3>
                    <div class="space-y-3">
                        <div class="flex items-center space-x-3 text-sm text-gray-600">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path
                                    d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z">
                                </path>
                            </svg>
                            <span>+7 (950) 336-29-89</span>
                        </div>
                    </div>
                </div>

                <!-- Рабочие часы -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">
                        Режим работы
                    </h3>
                    <div class="space-y-3">
                        <div class="flex items-start space-x-3 text-sm text-gray-600">
                            <svg class="w-4 h-4 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M12 6v6l4 2"></path>
                            </svg>
                            <div>
                                <div>Пн-Пт: 8:30 - 17:00</div>
                                <div>Обед: 12:30 - 13:00</div>
                                <div>Сб-Вс: выходные</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </footer>

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


    <!-- WebSocket Scripts -->
    @vite(['resources/js/websocket-client.js', 'resources/js/live-updates.js'])

    @stack('scripts')
</body>

</html>
