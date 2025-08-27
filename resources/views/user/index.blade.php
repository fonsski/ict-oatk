@extends('layouts.app')

@section('title', 'Управление пользователями - ICT')

@section('content')
<div class="container-width section-padding">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 mb-2">Управление пользователями</h1>
            <p class="text-slate-600">Администрирование учетных записей системы</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-3 mt-4 sm:mt-0">
            <a href="{{ route('user.statistics') }}" class="btn-outline">
                Статистика
            </a>
            <a href="{{ route('user.export') }}" class="btn-secondary">
                Экспорт CSV
            </a>
            <a href="{{ route('user.create') }}" class="btn-primary">
                Добавить пользователя
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="card p-6 text-center">
            <div class="text-2xl font-bold text-slate-900 mb-1">{{ $users->total() }}</div>
            <div class="text-sm text-slate-600">Всего пользователей</div>
        </div>
        <div class="card p-6 text-center">
            <div class="text-2xl font-bold text-slate-900 mb-1">{{ $users->where('is_active', true)->count() }}</div>
            <div class="text-sm text-slate-600">Активных</div>
        </div>
        <div class="card p-6 text-center">
            <div class="text-2xl font-bold text-slate-900 mb-1">{{ $users->where('is_active', false)->count() }}</div>
            <div class="text-sm text-slate-600">Неактивных</div>
        </div>
        <div class="card p-6 text-center">
            <div class="text-2xl font-bold text-slate-900 mb-1">{{ $users->where('created_at', '>=', now()->subDays(30))->count() }}</div>
            <div class="text-sm text-slate-600">За 30 дней</div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="card p-6 mb-8">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="search" class="form-label">Поиск</label>
                <input type="text"
                       id="search"
                       name="search"
                       value="{{ request('search') }}"
                       class="form-input"
                       placeholder="Имя или телефон">
            </div>
            <div>
                <label for="status" class="form-label">Статус</label>
                <select id="status" name="status" class="form-input">
                    <option value="">Все статусы</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Активные</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Неактивные</option>
                </select>
            </div>
            <div>
                <label for="role_id" class="form-label">Роль</label>
                <select id="role_id" name="role_id" class="form-input">
                    <option value="">Все роли</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" {{ request('role_id') == $role->id ? 'selected' : '' }}>
                            {{ $role->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn-primary btn-full">
                    Применить
                </button>
            </div>
        </form>
    </div>

    <!-- Bulk Actions -->
    <div class="card p-6 mb-8">
        <h3 class="text-lg font-semibold text-slate-900 mb-4">Массовые операции</h3>
        <form method="POST" action="{{ route('user.bulk-action') }}" id="bulk-form">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label for="bulk-action" class="form-label">Действие</label>
                    <select id="bulk-action" name="action" class="form-input" required>
                        <option value="">Выберите действие</option>
                        <option value="activate">Активировать</option>
                        <option value="deactivate">Деактивировать</option>
                        <option value="change_role">Изменить роль</option>
                        <option value="delete">Удалить</option>
                    </select>
                </div>
                <div id="role-select" class="hidden">
                    <label for="new_role_id" class="form-label">Новая роль</label>
                    <select id="new_role_id" name="new_role_id" class="form-input">
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn-primary btn-full" id="bulk-submit" disabled>
                        Применить
                    </button>
                </div>
                <div>
                    <button type="button" class="btn-secondary btn-full" onclick="selectAll()">
                        Выбрать всех
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Users Table -->
    <div class="card overflow-hidden">
        @if($users->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4 text-left">
                                <input type="checkbox" id="select-all" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            </th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900">Пользователь</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900">Роль</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900">Статус</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900">Дата регистрации</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900">Последний вход</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach($users as $user)
                            <tr class="hover:bg-slate-50 transition-colors duration-200">
                                <td class="px-6 py-4">
                                    <input type="checkbox"
                                           name="user_ids[]"
                                           value="{{ $user->id }}"
                                           form="bulk-form"
                                           class="user-checkbox rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                                            {{ substr($user->name, 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="font-medium text-slate-900">{{ $user->name }}</div>
                                            <div class="text-sm text-slate-600">{{ $user->formatted_phone }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ $user->role ? $user->role->name : 'Не назначена' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    @if($user->is_active)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Активен
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Неактивен
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-slate-600">
                                        <div>{{ $user->created_at->format('d.m.Y') }}</div>
                                        <div class="text-xs text-slate-500">{{ $user->created_at->format('H:i') }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-slate-600">
                                        @if($user->last_login_at)
                                            <div>{{ $user->last_login_at->format('d.m.Y') }}</div>
                                            <div class="text-xs text-slate-500">{{ $user->last_login_at->format('H:i') }}</div>
                                        @else
                                            <span class="text-slate-400">Никогда</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <a href="{{ route('user.show', $user) }}"
                                           class="text-blue-600 hover:text-blue-700 text-sm">
                                            Просмотр
                                        </a>
                                        <a href="{{ route('user.edit', $user) }}"
                                           class="text-green-600 hover:text-green-700 text-sm">
                                            Изменить
                                        </a>
                                        @if($user->id !== auth()->id())
                                            <form action="{{ route('user.toggle-status', $user) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit"
                                                        class="text-yellow-600 hover:text-yellow-700 text-sm"
                                                        onclick="return confirm('Изменить статус пользователя?')">
                                                    {{ $user->is_active ? 'Деактивировать' : 'Активировать' }}
                                                </button>
                                            </form>
                                            <form action="{{ route('user.destroy', $user) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="text-red-600 hover:text-red-700 text-sm"
                                                        onclick="return confirm('Удалить пользователя?')">
                                                    Удалить
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-16">
                <div class="mx-auto w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                    <div class="flex-shrink-0 flex items-center justify-center">
                        <!-- User icon removed -->
                    </div>
                </div>
                <h3 class="text-lg font-medium text-slate-900 mb-2">Пользователи не найдены</h3>
                <p class="text-slate-600 mb-6">Попробуйте изменить параметры поиска</p>
                <a href="{{ route('user.create') }}" class="btn-primary btn-lg">
                    Добавить первого пользователя
                </a>
            </div>
        @endif
    </div>

    <!-- Pagination -->
    @if($users->hasPages())
        <div class="mt-8">
            {{ $users->links() }}
        </div>
    @endif
</div>

@push('scripts')
<script>
    // Показать/скрыть выбор роли для массовых операций
    document.getElementById('bulk-action').addEventListener('change', function() {
        const roleSelect = document.getElementById('role-select');
        const bulkSubmit = document.getElementById('bulk-submit');

        if (this.value === 'change_role') {
            roleSelect.classList.remove('hidden');
            bulkSubmit.disabled = false;
        } else {
            roleSelect.classList.add('hidden');
            updateBulkSubmitState();
        }
    });

    // Обновление состояния кнопки массовых операций
    function updateBulkSubmitState() {
        const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
        const bulkSubmit = document.getElementById('bulk-submit');
        const bulkAction = document.getElementById('bulk-action');

        bulkSubmit.disabled = checkedBoxes.length === 0 || !bulkAction.value;
    }

    // Обработка изменения чекбоксов
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('user-checkbox')) {
            updateBulkSubmitState();
        }
    });

    // Выбрать всех пользователей
    function selectAll() {
        const selectAllCheckbox = document.getElementById('select-all');
        const userCheckboxes = document.querySelectorAll('.user-checkbox');

        userCheckboxes.forEach(checkbox => {
            checkbox.checked = selectAllCheckbox.checked;
        });

        updateBulkSubmitState();
    }

    // Обработка изменения "выбрать всех"
    document.getElementById('select-all').addEventListener('change', selectAll);

    // Подтверждение массовых операций
    document.getElementById('bulk-form').addEventListener('submit', function(e) {
        const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
        const action = document.getElementById('bulk-action').value;

        if (checkedBoxes.length === 0) {
            e.preventDefault();
            alert('Выберите пользователей для выполнения операции');
            return;
        }

        let message = '';
        switch(action) {
            case 'activate':
                message = `Активировать ${checkedBoxes.length} пользователей?`;
                break;
            case 'deactivate':
                message = `Деактивировать ${checkedBoxes.length} пользователей?`;
                break;
            case 'change_role':
                message = `Изменить роль для ${checkedBoxes.length} пользователей?`;
                break;
            case 'delete':
                message = `Удалить ${checkedBoxes.length} пользователей? Это действие нельзя отменить!`;
                break;
        }

        if (!confirm(message)) {
            e.preventDefault();
        }
    });

    // Живой поиск
    const searchInput = document.querySelector('input[name="search"]');
    const roleSelect = document.querySelector('select[name="role_id"]');
    const statusSelect = document.querySelector('select[name="active"]');
    const form = document.querySelector('form[method="GET"]');

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

                    // Найти новую таблицу в ответе
                    const newTable = tempDiv.querySelector('.overflow-x-auto');
                    const currentTable = document.querySelector('.overflow-x-auto');

                    if (newTable && currentTable) {
                        currentTable.innerHTML = newTable.innerHTML;
                    }

                    // Обновить пагинацию
                    const newPagination = tempDiv.querySelector('.mt-8');
                    const currentPagination = document.querySelector('.mt-8');

                    if (newPagination && currentPagination) {
                        currentPagination.innerHTML = newPagination.innerHTML;
                    }

                    hideLoadingIndicator();

                    // Переинициализировать обработчики событий для новых элементов
                    reinitializeEventHandlers();
                })
                .catch(error => {
                    console.error('Ошибка поиска:', error);
                    hideLoadingIndicator();
                });
        }, 300); // Задержка 300ms для избежания избыточных запросов
    }

    function showLoadingIndicator() {
        const table = document.querySelector('.overflow-x-auto');
        if (table) {
            table.style.opacity = '0.5';
        }
    }

    function hideLoadingIndicator() {
        const table = document.querySelector('.overflow-x-auto');
        if (table) {
            table.style.opacity = '1';
        }
    }

    function reinitializeEventHandlers() {
        // Переинициализировать чекбоксы после обновления содержимого
        document.querySelectorAll('.user-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', updateBulkSubmitState);
        });

        const selectAllCheckbox = document.getElementById('select-all');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', selectAll);
        }
    }

    // Добавить обработчики событий для живого поиска
    if (searchInput) {
        searchInput.addEventListener('input', performSearch);
    }

    if (roleSelect) {
        roleSelect.addEventListener('change', performSearch);
    }

    if (statusSelect) {
        statusSelect.addEventListener('change', performSearch);
    }
</script>
@endpush
@endsection
