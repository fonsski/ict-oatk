@extends('layouts.app')

@section('title', 'Тестирование уведомлений - ICT')

@section('content')
<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">🔔 Тестирование системы уведомлений</h1>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                Эта страница предназначена для тестирования и отладки системы уведомлений в реальном времени
            </p>
        </div>

        <!-- Status Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Current User -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="bg-blue-100 rounded-full p-3">
                        <svg class="w-6 h-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Текущий пользователь</p>
                        <p class="text-lg font-semibold text-gray-900">{{ auth()->user()->name }}</p>
                        <p class="text-sm text-gray-600">{{ auth()->user()->email }}</p>
                    </div>
                </div>
            </div>

            <!-- Notification Count -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="bg-green-100 rounded-full p-3">
                        <svg class="w-6 h-6 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5-5-5h5V3h0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Непрочитанных</p>
                        <p class="text-lg font-semibold text-gray-900" id="unread-count-display">Загрузка...</p>
                        <button onclick="refreshCount()" class="text-sm text-blue-600 hover:text-blue-800">
                            Обновить
                        </button>
                    </div>
                </div>
            </div>

            <!-- System Status -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="bg-yellow-100 rounded-full p-3">
                        <svg class="w-6 h-6 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Статус API</p>
                        <p class="text-lg font-semibold" id="api-status">Проверка...</p>
                        <p class="text-sm text-gray-600" id="last-check">--</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Test Actions -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Тестовые действия</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <a href="{{ route('test.notifications') }}"
                   class="inline-flex items-center justify-center px-4 py-3 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5-5-5h5V3h0z" />
                    </svg>
                    Создать тестовое уведомление
                </a>
                <a href="{{ route('test.ticket') }}"
                   class="inline-flex items-center justify-center px-4 py-3 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                    </svg>
                    Создать тестовую заявку
                </a>
                <button onclick="testPolling()"
                        class="inline-flex items-center justify-center px-4 py-3 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Тест polling
                </button>
                <button onclick="markAllRead()"
                        class="inline-flex items-center justify-center px-4 py-3 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Отметить все как прочитанные
                </button>
            </div>
        </div>

        <!-- Debug Console -->
        <div class="bg-gray-900 rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-white">Консоль отладки</h2>
                <button onclick="clearDebugLog()" class="text-sm text-gray-400 hover:text-white">
                    Очистить
                </button>
            </div>
            <div id="debug-console" class="bg-black rounded p-4 h-64 overflow-y-auto font-mono text-sm text-green-400">
                <div class="text-gray-500">Консоль готова к работе...</div>
            </div>
        </div>

        <!-- Current Notifications -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-gray-900">Текущие уведомления</h2>
                <button onclick="loadCurrentNotifications()" class="text-sm text-blue-600 hover:text-blue-800">
                    Обновить список
                </button>
            </div>
            <div id="notifications-list" class="space-y-3">
                <div class="text-center text-gray-500 py-8">Нажмите "Обновить список" для загрузки</div>
            </div>
        </div>
    </div>
</div>

<script>
let debugLog = [];

function log(message, type = 'info') {
    const timestamp = new Date().toLocaleTimeString('ru-RU');
    const logEntry = `[${timestamp}] ${message}`;
    debugLog.push({ time: timestamp, message, type });

    const console = document.getElementById('debug-console');
    const colorClass = type === 'error' ? 'text-red-400' :
                      type === 'success' ? 'text-green-400' :
                      type === 'warning' ? 'text-yellow-400' : 'text-blue-400';

    console.innerHTML += `<div class="${colorClass}">${logEntry}</div>`;
    console.scrollTop = console.scrollHeight;
}

function clearDebugLog() {
    debugLog = [];
    document.getElementById('debug-console').innerHTML = '<div class="text-gray-500">Консоль очищена...</div>';
}

function refreshCount() {
    log('Обновление счетчика уведомлений...');

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
        log(`Получен ответ: ${response.status} ${response.statusText}`);
        return response.json();
    })
    .then(data => {
        log(`Данные: ${JSON.stringify(data)}`, 'success');
        document.getElementById('unread-count-display').textContent = data.unread_count;
        document.getElementById('api-status').textContent = 'Работает';
        document.getElementById('api-status').className = 'text-lg font-semibold text-green-600';
        document.getElementById('last-check').textContent = new Date().toLocaleTimeString('ru-RU');
    })
    .catch(error => {
        log(`Ошибка: ${error.message}`, 'error');
        document.getElementById('api-status').textContent = 'Ошибка';
        document.getElementById('api-status').className = 'text-lg font-semibold text-red-600';
    });
}

function testPolling() {
    log('Тестирование polling...');

    fetch('{{ route("api.notifications.poll") }}', {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        log(`Polling результат: ${JSON.stringify(data)}`, 'success');
    })
    .catch(error => {
        log(`Ошибка polling: ${error.message}`, 'error');
    });
}

function markAllRead() {
    log('Отмечаем все уведомления как прочитанные...');

    fetch('{{ route("api.notifications.mark-all-as-read") }}', {
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
        log(`Результат: ${JSON.stringify(data)}`, 'success');
        refreshCount();
        loadCurrentNotifications();
    })
    .catch(error => {
        log(`Ошибка: ${error.message}`, 'error');
    });
}

function loadCurrentNotifications() {
    log('Загрузка текущих уведомлений...');

    const listContainer = document.getElementById('notifications-list');
    listContainer.innerHTML = '<div class="text-center text-gray-500 py-4">Загрузка...</div>';

    fetch('{{ route("api.notifications.index") }}?limit=20', {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        log(`Загружено уведомлений: ${data.notifications.length}`, 'success');

        if (data.notifications.length === 0) {
            listContainer.innerHTML = '<div class="text-center text-gray-500 py-8">Нет уведомлений</div>';
            return;
        }

        let html = '';
        data.notifications.forEach(notification => {
            const isRead = notification.read_at !== null;
            const createdAt = new Date(notification.created_at).toLocaleString('ru-RU');

            html += `
                <div class="border rounded-lg p-4 ${!isRead ? 'bg-blue-50 border-blue-200' : 'bg-gray-50 border-gray-200'}">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-2">
                                <h3 class="font-medium text-gray-900">${notification.title}</h3>
                                ${!isRead ? '<span class="w-2 h-2 bg-blue-500 rounded-full"></span>' : ''}
                            </div>
                            <p class="text-gray-600 mt-1">${notification.message}</p>
                            <p class="text-xs text-gray-500 mt-2">
                                ${createdAt} | Тип: ${notification.type} | ID: ${notification.id}
                            </p>
                        </div>
                        ${!isRead ? `<button onclick="markAsRead('${notification.id}')" class="text-sm text-blue-600 hover:text-blue-800">Прочитано</button>` : ''}
                    </div>
                </div>
            `;
        });

        listContainer.innerHTML = html;
    })
    .catch(error => {
        log(`Ошибка загрузки уведомлений: ${error.message}`, 'error');
        listContainer.innerHTML = '<div class="text-center text-red-500 py-4">Ошибка загрузки</div>';
    });
}

function markAsRead(notificationId) {
    log(`Отмечаем уведомление ${notificationId} как прочитанное...`);

    fetch(`{{ url('/api/notifications/mark-as-read') }}/${notificationId}`, {
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
        log(`Результат: ${JSON.stringify(data)}`, 'success');
        refreshCount();
        loadCurrentNotifications();
    })
    .catch(error => {
        log(`Ошибка: ${error.message}`, 'error');
    });
}

// Автоматическая проверка при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    log('Страница загружена, выполняем начальную проверку...', 'success');
    refreshCount();
});
</script>
@endsection
