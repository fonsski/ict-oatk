<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ICT')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <a href="{{ route('home') }}" class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 18v-6a9 9 0 0 1 18 0v6"></path><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"></path>
                        </svg>
                    </div>
                    <span class="text-xl font-bold text-gray-900">ICT</span>
                </a>

                <!-- Desktop Navigation -->
                <nav class="hidden md:flex items-center space-x-6">
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

                        @if(user_can_manage_tickets())
                        <a href="{{ route('all-tickets.index') }}"
                           class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('all-tickets.*') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' }}">
                            Все заявки
                        </a>
                        @endif

                        @if(auth()->check() && auth()->user()->hasRole(['admin', 'master', 'technician']))
                            <a href="{{ route('knowledge.index') }}"
                               class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('knowledge.index') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' }}">
                                База знаний
                            </a>
                        @endif

                        @if(user_can_manage_users())
                            <!-- Admin Dropdown Menu -->
                            <div class="relative">
                                <button type="button" id="admin-menu-button" class="flex items-center space-x-1 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs(['equipment.*', 'room.*', 'user.*', 'homepage-faq.*', 'drawing-canvas.*']) ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' }}">
                                    <span>Управление</span>
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="6,9 12,15 18,9"></polyline>
                                    </svg>
                                </button>

                                <!-- Admin Dropdown -->
                                <div id="admin-dropdown" class="hidden absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg border border-gray-200 z-[100]">
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
                                        <a href="{{ route('drawing-canvas.index') }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('drawing-canvas.*') ? 'bg-blue-50 text-blue-600' : '' }}">
                                            Инструмент рисования
                                        </a>

                                        @if(config('app.debug'))
                                        <div class="border-t border-gray-200 my-1"></div>
                                        <div class="px-4 py-2 text-xs text-gray-500 uppercase tracking-wider">Тестирование</div>
                                        <a href="{{ route('test.debug') }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            🐛 Debug уведомлений
                                        </a>
                                        <a href="{{ route('test.notifications') }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            🔔 Тест уведомления
                                        </a>
                                        <a href="{{ route('test.ticket') }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            🎫 Тест заявки
                                        </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Notifications Menu -->
                        @if(user_can_manage_tickets())
                        <div class="relative">
                            <button type="button" id="notifications-menu-button" class="relative p-2 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-md">
                                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                    <path d="m13.73 21a2 2 0 0 1-3.46 0"></path>
                                </svg>
                                <span id="notification-badge" class="hidden absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">0</span>
                            </button>

                            <!-- Notifications Dropdown -->
                            <div id="notifications-dropdown" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-md shadow-lg border border-gray-200 z-[100]">
                                <div class="py-2">
                                    <div class="px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                                        <h3 class="text-sm font-semibold text-gray-900">Уведомления</h3>
                                        <button id="mark-all-read" class="text-xs text-blue-600 hover:text-blue-700">Отметить все как прочитанные</button>
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
                            <button type="button" id="user-menu-button" class="flex items-center space-x-2 px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:text-blue-600 hover:bg-blue-50">
                                <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                                    {{ substr(auth()->user()->name, 0, 1) }}
                                </div>
                                <span>{{ auth()->user()->name }}</span>
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="6,9 12,15 18,9"></polyline>
                                </svg>
                            </button>

                            <!-- Dropdown Menu -->
                            <div id="user-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg border border-gray-200 z-[100]">
                                <div class="py-1">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            Выход
                            </button>
                        </form>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="flex items-center space-x-4">
                            <a href="{{ route('login') }}" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-blue-600">
                                Войти
                            </a>

                            <a href="{{ route('register') }}" class="px-4 py-2 text-sm font-medium bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                Регистрация
                            </a>
                        </div>
                    @endauth
                </nav>

                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button type="button" id="mobile-menu-button" class="text-gray-500 hover:text-gray-600 p-2">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation -->
        <div id="mobile-menu" class="hidden md:hidden bg-white border-t border-gray-200">
            <div class="px-2 pt-2 pb-3 space-y-1">
                @auth
                <a href="{{ route('home') }}"
                       class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('home') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' }}">
                        Главная
                </a>

                <a href="{{ route('tickets.create') }}"
                       class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('ticket.create') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' }}">
                        Подать заявку
                    </a>

                    <a href="{{ route('tickets.index') }}"
                       class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('tickets.index') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' }}">
                        Мои заявки
                </a>

                @if(user_can_manage_tickets())
                <a href="{{ route('all-tickets.index') }}"
                       class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('all-tickets.*') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' }}">
                        Все заявки
                </a>
                @endif

                @if(auth()->check() && auth()->user()->hasRole(['admin', 'master', 'technician']))
                    <a href="{{ route('knowledge.index') }}"
                           class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('knowledge.index') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' }}">
                        База знаний
                    </a>
                @endif

                    @if(user_can_manage_users())
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
                            <a href="{{ route('drawing-canvas.index') }}"
                               class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('drawing-canvas.*') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' }}">
                                Инструмент рисования
                            </a>
                        </div>
                    @endif

                    <!-- Mobile User Menu -->
                    <div class="border-t border-gray-200 pt-4 mt-4">
                        <div class="flex items-center space-x-3 px-3 py-2">
                            <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                            <span class="text-base font-medium text-gray-900">{{ auth()->user()->name }}</span>
                        </div>

                        <form method="POST" action="{{ route('logout') }}" class="mt-2">
                            @csrf
                            <button type="submit" class="w-full text-left px-3 py-2 text-base font-medium text-gray-600 hover:text-blue-600 hover:bg-blue-50">
                                Выход
                            </button>
                        </form>
                    </div>
                @else
                    <div class="space-y-2">
                        <a href="{{ route('login') }}" class="block px-3 py-2 text-base font-medium text-gray-600 hover:text-blue-600">
                            Войти
                        </a>

                        <a href="{{ route('register') }}" class="block px-3 py-2 text-base font-medium bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Регистрация
                        </a>
                    </div>
                @endauth
            </div>
        </div>
    </header>

    <!-- Notifications Container -->
    <div id="notifications-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

    <!-- Main Content -->
    <main class="flex-1">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-50 border-t border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Logo and Description -->
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center space-x-2 mb-4">
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 18v-6a9 9 0 0 1 18 0v6"></path><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"></path>
                            </svg>
                        </div>
                        <span class="text-xl font-bold text-gray-900">ICT</span>
                    </div>
                    <p class="text-gray-600 mb-4 max-w-md">
                        Современная система технической поддержки для колледжа.
                        Быстро, удобно, эффективно.
                    </p>
                </div>

                <!-- Contact Information -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">
                        Контакты
                    </h3>
                    <div class="space-y-3">
                        {{-- <div class="flex items-center space-x-3 text-sm text-gray-600">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="20" height="16" x="2" y="4" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                            </svg>
                            <span>support@college.edu</span>
                        </div> --}}
                        <div class="flex items-center space-x-3 text-sm text-gray-600">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                            <span>+7 (950) 336-29-89</span>
                        </div>
                    </div>
                </div>

                <!-- Working Hours -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">
                        Режим работы
                    </h3>
                    <div class="space-y-3">
                        <div class="flex items-start space-x-3 text-sm text-gray-600">
                            <svg class="w-4 h-4 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path>
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
            document.addEventListener('DOMContentLoaded', function() {
                // Получаем ссылки на кнопки
                const userButton = document.getElementById('user-menu-button');
                const adminButton = document.getElementById('admin-menu-button');
                const notificationsButton = document.getElementById('notifications-menu-button');
                const mobileButton = document.getElementById('mobile-menu-button');

                console.log('DOM loaded, buttons:', {
                    userButton: !!userButton,
                    adminButton: !!adminButton,
                    notificationsButton: !!notificationsButton,
                    mobileButton: !!mobileButton
                });

                // Mobile menu toggle
                if (mobileButton) {
                    mobileButton.addEventListener('click', function() {
                        const menu = document.getElementById('mobile-menu');
                        if (menu) {
                            menu.classList.toggle('hidden');
                        }
                    });
                }

                // User dropdown toggle
                if (userButton) {
                    userButton.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        const dropdown = document.getElementById('user-dropdown');
                        const adminDropdown = document.getElementById('admin-dropdown');
                        const notificationsDropdown = document.getElementById('notifications-dropdown');

                        console.log('User button clicked, dropdown exists:', !!dropdown);

                        if (dropdown) {
                            dropdown.classList.toggle('hidden');
                            console.log('User dropdown is now:', dropdown.classList.contains('hidden') ? 'hidden' : 'visible');
                        }

                        // Close other dropdowns
                        if (adminDropdown) {
                            adminDropdown.classList.add('hidden');
                        }
                        if (notificationsDropdown) {
                            notificationsDropdown.classList.add('hidden');
                        }
                    });
                }

                // Notifications dropdown toggle
                if (notificationsButton) {
                    notificationsButton.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        const dropdown = document.getElementById('notifications-dropdown');
                        const userDropdown = document.getElementById('user-dropdown');
                        const adminDropdown = document.getElementById('admin-dropdown');

                        console.log('Notifications button clicked, dropdown exists:', !!dropdown);

                        if (dropdown) {
                            const isHidden = dropdown.classList.contains('hidden');
                            dropdown.classList.toggle('hidden');
                            console.log('Notifications dropdown is now:', dropdown.classList.contains('hidden') ? 'hidden' : 'visible');

                            // Load notifications when opening dropdown
                            if (isHidden && typeof loadNotifications === 'function') {
                                loadNotifications();
                            }
                        }

                        // Close other dropdowns
                        if (userDropdown) {
                            userDropdown.classList.add('hidden');
                        }
                        if (adminDropdown) {
                            adminDropdown.classList.add('hidden');
                        }
                    });
                }

                // Admin dropdown toggle
                if (adminButton) {
                    adminButton.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        const dropdown = document.getElementById('admin-dropdown');
                        const userDropdown = document.getElementById('user-dropdown');
                        const notificationsDropdown = document.getElementById('notifications-dropdown');

                        console.log('Admin button clicked, dropdown exists:', !!dropdown);

                        if (dropdown) {
                            dropdown.classList.toggle('hidden');
                            console.log('Admin dropdown is now:', dropdown.classList.contains('hidden') ? 'hidden' : 'visible');
                        }

                        // Close other dropdowns
                        if (userDropdown) {
                            userDropdown.classList.add('hidden');
                        }
                        if (notificationsDropdown) {
                            notificationsDropdown.classList.add('hidden');
                        }
                    });
                }

                // Close dropdowns when clicking outside
                document.addEventListener('click', function(event) {
                    const userDropdown = document.getElementById('user-dropdown');
                    const adminDropdown = document.getElementById('admin-dropdown');
                    const notificationsDropdown = document.getElementById('notifications-dropdown');

                    // Close user dropdown
                    if (userButton && userDropdown && !userButton.contains(event.target) && !userDropdown.contains(event.target)) {
                        userDropdown.classList.add('hidden');
                        console.log('User dropdown closed by outside click');
                    }

                    // Close admin dropdown
                    if (adminButton && adminDropdown && !adminButton.contains(event.target) && !adminDropdown.contains(event.target)) {
                        adminDropdown.classList.add('hidden');
                        console.log('Admin dropdown closed by outside click');
                    }

                    // Close notifications dropdown
                    if (notificationsButton && notificationsDropdown && !notificationsButton.contains(event.target) && !notificationsDropdown.contains(event.target)) {
                        notificationsDropdown.classList.add('hidden');
                        console.log('Notifications dropdown closed by outside click');
                    }
                });

                // Force initialization check - this ensures all elements are found
                console.log('Checking dropdown elements on page:');
                console.log('- User dropdown:', !!document.getElementById('user-dropdown'));
                console.log('- Admin dropdown:', !!document.getElementById('admin-dropdown'));
                console.log('- Notifications dropdown:', !!document.getElementById('notifications-dropdown'));

                // Mark all read button
                const markAllReadButton = document.getElementById('mark-all-read');
                if (markAllReadButton) {
                    markAllReadButton.addEventListener('click', function() {
                        markAllNotificationsAsRead();
                    });
                }
        });

        // Notification System
        window.showNotification = function(message, type = 'info', duration = 5000) {
            const container = document.getElementById('notifications-container');
            const notification = document.createElement('div');

            const typeClasses = {
                'success': 'bg-green-100 border-green-500 text-green-700',
                'error': 'bg-red-100 border-red-500 text-red-700',
                'warning': 'bg-yellow-100 border-yellow-500 text-yellow-700',
                'info': 'bg-blue-100 border-blue-500 text-blue-700'
            };

            notification.className = `border-l-4 p-4 rounded shadow-lg max-w-sm transform translate-x-full transition-transform duration-300 ${typeClasses[type] || typeClasses.info}`;
            notification.innerHTML = `
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <span class="text-sm font-medium">${message}</span>
                    </div>
                    <button class="ml-4 text-gray-400 hover:text-gray-600" onclick="this.parentElement.parentElement.remove()">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
            `;

            container.appendChild(notification);

            // Animate in
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);

            // Auto remove
            if (duration > 0) {
                setTimeout(() => {
                    notification.classList.add('translate-x-full');
                    setTimeout(() => notification.remove(), 300);
                }, duration);
            }
        };

        // Show Laravel flash messages as notifications
        @if(session('success'))
            showNotification('{{ session('success') }}', 'success');
        @endif

        @if(session('error'))
            showNotification('{{ session('error') }}', 'error');
        @endif

        @if(session('warning'))
            showNotification('{{ session('warning') }}', 'warning');
        @endif

        @if(session('info'))
            showNotification('{{ session('info') }}', 'info');
        @endif

        // Global variables for notification tracking
        let lastNotificationCheck = new Date().toISOString();
        let notificationPollingInterval = null;
        let isPolling = false;

        // Update notification badge with real API call
        function updateNotificationBadge() {
            if (isPolling) {
                console.log('[Notifications] Polling already in progress, skipping...');
                return;
            }

            console.log('[Notifications] Checking for unread notifications...');
            isPolling = true;

            fetch('{{ route("api.notifications.unread-count") }}', {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => {
                console.log('[Notifications] Response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('[Notifications] Data received:', data);
                const badge = document.getElementById('notification-badge');
                if (badge) {
                    if (data.unread_count > 0) {
                        console.log(`[Notifications] Showing badge with count: ${data.unread_count}`);
                        badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                        badge.classList.remove('hidden');
                    } else {
                        console.log('[Notifications] No unread notifications, hiding badge');
                        badge.classList.add('hidden');
                    }
                } else {
                    console.warn('[Notifications] Badge element not found!');
                }

                // Update last check time
                lastNotificationCheck = data.last_updated;
            })
            .catch(error => {
                console.error('[Notifications] Error fetching notifications:', error);
                // В случае ошибки просто скрываем badge
                const badge = document.getElementById('notification-badge');
                if (badge) {
                    badge.classList.add('hidden');
                }
            })
            .finally(() => {
                isPolling = false;
                console.log('[Notifications] Polling completed');
            });
        }

        // Poll for new notifications
        function pollForNewNotifications() {
            if (isPolling) return;

            isPolling = true;

            fetch(`{{ route("api.notifications.poll") }}?last_check=${encodeURIComponent(lastNotificationCheck)}`, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.has_new) {
                    // Обновляем badge
                    const badge = document.getElementById('notification-badge');
                    if (badge && data.unread_count > 0) {
                        badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                        badge.classList.remove('hidden');
                    }

                    // Показываем уведомление о новых заявках
                    if (data.new_notifications && data.new_notifications.length > 0) {
                        data.new_notifications.forEach(notification => {
                                if (notification.type === 'new_ticket') {
                                    // Разный тип уведомления в зависимости от приоритета
                                    let notificationType = 'info';
                                    if (notification.data.ticket_priority === 'high') {
                                        notificationType = 'warning';
                                    } else if (notification.data.ticket_priority === 'urgent') {
                                        notificationType = 'error';
                                    }
                                    showNotification(`Новая заявка: ${notification.data.ticket_title}`, notificationType);
                                } else if (notification.type === 'ticket_status_changed') {
                                    // Тип уведомления в зависимости от нового статуса
                                    let notificationType = 'info';
                                    if (notification.data.new_status === 'resolved') {
                                        notificationType = 'success';
                                    } else if (notification.data.new_status === 'closed') {
                                        notificationType = 'warning';
                                    }
                                    showNotification(notification.message, notificationType);
                                } else if (notification.type === 'ticket_assigned') {
                                    showNotification(notification.message, 'success');
                                } else {
                                    // Для всех остальных типов
                                    showNotification(notification.message, 'info');
                                }
                            });
                        }
                    }

                    lastNotificationCheck = data.last_updated;
            })
            .catch(error => {
                console.error('Error polling notifications:', error);
            })
            .finally(() => {
                isPolling = false;
            });
        }

        // Load notifications dropdown content
        function loadNotifications() {
            console.log('[Notifications] Loading notifications dropdown...');
            const dropdownContent = document.getElementById('notifications-list');
            if (!dropdownContent) {
                console.warn('[Notifications] Dropdown content element not found!');
                return;
            }

            dropdownContent.innerHTML = '<div class="p-4 text-center text-gray-500">Загрузка...</div>';

            fetch('{{ route("api.notifications.index") }}?limit=10', {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => {
                console.log('[Notifications] Dropdown response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('[Notifications] Dropdown data:', data);

                if (!data.notifications || data.notifications.length === 0) {
                    dropdownContent.innerHTML = '<div class="p-4 text-center text-gray-500">Нет уведомлений</div>';
                    return;
                }

                let html = '';
                data.notifications.forEach(function(notification) {
                    const isRead = notification.read_at !== null;
                    const createdAt = new Date(notification.created_at).toLocaleString('ru-RU', {
                        day: '2-digit',
                        month: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit'
                    });

                    html += '<div class="relative block px-4 py-3 border-b border-gray-100 hover:bg-gray-50 ' + (isRead ? '' : 'bg-blue-50') + '" data-notification-id="' + notification.id + '">';
                    html += '<div class="flex items-start justify-between">';
                    html += '<div class="flex-1">';
                    html += '<p class="text-sm font-medium text-gray-900">' + notification.title + '</p>';
                    html += '<p class="text-sm text-gray-600 mt-1">' + notification.message + '</p>';
                    html += '<p class="text-xs text-gray-500 mt-2">' + createdAt + '</p>';
                    html += '</div>';

                    if (!isRead) {
                        html += '<div class="w-2 h-2 bg-blue-500 rounded-full ml-2 mt-1"></div>';
                    }

                    html += '</div>';

                    if (notification.url) {
                        html += '<a href="' + notification.url + '" class="block absolute inset-0" data-id="' + notification.id + '"></a>';
                    }

                    html += '</div>'; // Close the notification item div
                });

                dropdownContent.innerHTML = html;
                console.log('[Notifications] Rendered ' + data.notifications.length + ' notifications');

                // Add click handlers for notification links
                const links = dropdownContent.querySelectorAll('[data-id]');
                links.forEach(function(link) {
                    link.addEventListener('click', function(event) {
                        const notificationId = this.getAttribute('data-id');
                        markNotificationAsRead(notificationId);
                    });
                });
            })
            .catch(function(error) {
                console.error('[Notifications] Error loading notifications:', error);
                dropdownContent.innerHTML = '<div class="p-4 text-center text-red-500">Ошибка загрузки</div>';
            });
        }

        // Mark notification as read
        function markNotificationAsRead(notificationId) {
            console.log('[Notifications] Marking as read:', notificationId);

            fetch(`{{ route('api.notifications.mark-as-read', ['notificationId' => '__ID__']) }}`.replace('__ID__', notificationId), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the badge count
                    updateNotificationBadge();
                }
            })
            .catch(error => {
                console.error('[Notifications] Error marking as read:', error);
            });
        }

        // Mark all notifications as read
        function markAllNotificationsAsRead() {
            fetch('{{ route('api.notifications.mark-all-as-read') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const badge = document.getElementById('notification-badge');
                    if (badge) {
                        badge.classList.add('hidden');
                    }
                    loadNotifications(); // Refresh the dropdown
                    showNotification('Все уведомления отмечены как прочитанные', 'success');
                }
            })
            .catch(error => {
                console.error('Error marking all notifications as read:', error);
            });
        }

        // Start polling when page loads
        function startNotificationPolling() {
            console.log('[Notifications] Setting up polling interval (30 seconds)...');

            // Set up polling every 30 seconds for better performance
            notificationPollingInterval = setInterval(() => {
                console.log('[Notifications] Polling interval triggered');
                updateNotificationBadge();
            }, 30000);

            // Also check when tab becomes visible again
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) {
                    console.log('[Notifications] Tab became visible, checking notifications...');
                    setTimeout(updateNotificationBadge, 1000);
                }
            });
        }

        // Stop polling when page is about to unload
        function stopNotificationPolling() {
            if (notificationPollingInterval) {
                clearInterval(notificationPollingInterval);
                notificationPollingInterval = null;
            }
        }

        // Initialize notification system
        console.log('[Notifications] Initializing notification system...');
        console.log('[Notifications] User can manage tickets:', @json(user_can_manage_tickets()));
        console.log('[Notifications] Current user:', @json(auth()->user() ? auth()->user()->name : 'Guest'));

        // Check if notification elements exist
        const notificationsButton = document.getElementById('notifications-menu-button');
        const notificationBadge = document.getElementById('notification-badge');
        const notificationsDropdown = document.getElementById('notifications-dropdown');

        // Initial check after page load
        setTimeout(() => {
            console.log('[Notifications] Running initial notification check...');
            updateNotificationBadge();
        }, 1000);

        // Start polling
        startNotificationPolling();
        window.addEventListener('beforeunload', stopNotificationPolling);
    </script>

    <!-- Common JS libraries -->
    <script>
        // Функция для загрузки Fabric.js по требованию
        window.loadFabricJS = function(callback) {
            // Если библиотека уже загружена, просто выполняем callback
            if (typeof fabric !== 'undefined') {
                console.log('Fabric.js уже загружен, версия:', fabric.version);
                if (callback && typeof callback === 'function') {
                    callback(true);
                }
                return true;
            }

            console.log('Загрузка Fabric.js...');

            // Создаем и добавляем скрипт
            var script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js';
            script.async = true;

            // Обработчик успешной загрузки
            script.onload = function() {
                console.log('Fabric.js успешно загружен, версия:', fabric.version);
                if (callback && typeof callback === 'function') {
                    callback(true);
                }
            };

            // Обработчик ошибки загрузки
            script.onerror = function() {
                console.error('Не удалось загрузить Fabric.js');
                if (callback && typeof callback === 'function') {
                    callback(false);
                }
            };

            document.head.appendChild(script);
        };

        // Инициализация при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            // Проверяем, есть ли на странице canvas-container
            if (document.getElementById('canvas-container')) {
                console.log('Обнаружен canvas-container, загружаем Fabric.js');
                window.loadFabricJS();
            }
        });
    </script>

    @stack('scripts')
</body>
</html>
