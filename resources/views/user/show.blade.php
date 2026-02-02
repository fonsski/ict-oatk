@extends('layouts.app')

@section('title', 'Пользователь ' . $user->name . ' - ICT')

@section('content')
<div class="container-width section-padding">
    <!-- Header -->
            <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-slate-900 mb-2">Пользователь: {{ $user->name }}</h1>
                <p class="text-slate-600">Детальная информация об учетной записи</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('user.edit', $user) }}" class="btn-primary">
                    Редактировать
                </a>
                <a href="{{ route('user.index') }}" class="btn-outline">
                    Назад к списку
                </a>
            </div>
        </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-8">
            <!-- User Profile Card -->
            <div class="card p-8">
                <div class="flex items-start space-x-6">
                    <div class="flex-1">
                        <h2 class="text-2xl font-bold text-slate-900 mb-2">{{ $user->name }}</h2>
                        <p class="text-slate-600 mb-4">{{ $user->formatted_phone }}</p>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <div class="text-sm text-slate-500 mb-1">Роль в системе</div>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                    {{ $user->role ? $user->role->name : 'Не назначена' }}
                                </span>
                            </div>
                            <div>
                                <div class="text-sm text-slate-500 mb-1">Статус</div>
                                @if($user->is_active)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                        Активен
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                        Неактивен
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Statistics -->
            <div class="card p-8">
                <h3 class="text-xl font-semibold text-slate-900 mb-6">Статистика активности</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-blue-600 mb-1">{{ $stats['total_tickets'] }}</div>
                        <div class="text-sm text-slate-600">Всего заявок</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-yellow-600 mb-1">{{ $stats['open_tickets'] }}</div>
                        <div class="text-sm text-slate-600">Открытых</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-green-600 mb-1">{{ $stats['resolved_tickets'] }}</div>
                        <div class="text-sm text-slate-600">Решенных</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-purple-600 mb-1">{{ $stats['total_comments'] }}</div>
                        <div class="text-sm text-slate-600">Комментариев</div>
                    </div>
                </div>
            </div>

            <!-- Recent Tickets -->
            @if($user->tickets->count() > 0)
                <div class="card p-8">
                    <h3 class="text-xl font-semibold text-slate-900 mb-6">Последние заявки</h3>
                    <div class="space-y-4">
                        @foreach($user->tickets->take(5) as $ticket)
                            <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg">
                                <div class="flex items-center space-x-4">
                                    <span class="font-semibold">#{{ $ticket->id }}</span>
                                    <div>
                                        <div class="font-medium text-slate-900 break-words line-clamp-2">{{ $ticket->title }}</div>
                                        <div class="text-sm text-slate-600">{{ Str::limit($ticket->description, 100) }}</div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm text-slate-600">{{ $ticket->created_at->format('d.m.Y') }}</div>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                        @if($ticket->status === 'open') bg-red-100 text-red-800
                                        @elseif($ticket->status === 'in_progress') bg-yellow-100 text-yellow-800
                                        @elseif($ticket->status === 'resolved') bg-green-100 text-green-800
                                        @else bg-slate-100 text-slate-800 @endif">
                                        {{ ucfirst($ticket->status) }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if($user->tickets->count() > 5)
                        <div class="mt-6 text-center">
                            <a href="#" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                                Показать все заявки ({{ $user->tickets->count() }})
                            </a>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- User Details -->
            <div class="card p-6">
                <h3 class="text-lg font-semibold text-slate-900 mb-4">Детали учетной записи</h3>
                <div class="space-y-4">
                    <div>
                        <div class="text-sm text-slate-500 mb-1">Дата регистрации</div>
                        <div class="text-sm text-slate-900">{{ $user->created_at->format('d.m.Y H:i') }}</div>
                    </div>

                    @if($user->last_login_at)
                        <div>
                            <div class="text-sm text-slate-500 mb-1">Последний вход</div>
                            <div class="text-sm text-slate-900">{{ $user->last_login_at->format('d.m.Y H:i') }}</div>
                        </div>
                    @else
                        <div>
                            <div class="text-sm text-slate-500 mb-1">Последний вход</div>
                            <div class="text-sm text-slate-400">Никогда</div>
                        </div>
                    @endif

                    <div>
                        <div class="text-sm text-slate-500 mb-1">Телефон подтвержден</div>
                        <div class="text-sm text-slate-900">
                            @if($user->phone_verified_at)
                                <span class="text-green-600">Да</span>
                            @else
                                <span class="text-red-600">Нет</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Role Information -->
            @if($user->role)
                <div class="card p-6">
                    <h3 class="text-lg font-semibold text-slate-900 mb-4">Информация о роли</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <div class="text-sm text-slate-500 mb-1">Описание</div>
                            <div class="font-medium text-slate-900">{{ $user->role->name }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-slate-500 mb-1">Описание</div>
                            <div class="text-sm text-slate-700">{{ $user->role ? $user->role->description : 'Не указано' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-slate-500 mb-1">Уровень доступа</div>
                            <div class="text-sm text-slate-700">{{ $user->role ? $user->role->slug : 'Не указано' }}</div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Quick Actions -->
            <div class="card p-6">
                <h3 class="text-lg font-semibold text-slate-900 mb-4">Быстрые действия</h3>
                <div class="space-y-3">
                    @if($user->id !== auth()->id())
                        <!-- Reset Password -->
                        <button type="button"
                                onclick="showResetPasswordModal()"
                                class="w-full text-left px-3 py-2 text-sm rounded-md hover:bg-blue-50 transition-colors duration-200">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                                <span class="text-blue-600">Сбросить пароль</span>
                            </div>
                        </button>

                        <!-- Toggle Status -->
                        <form action="{{ route('user.toggle-status', $user) }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="w-full text-left px-3 py-2 text-sm rounded-md hover:bg-yellow-50 transition-colors duration-200"
                                    onclick="return confirm('Изменить статус пользователя?')">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4 text-yellow-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="8" x2="12" y2="16"></line>
                                        <line x1="8" y1="12" x2="16" y2="12"></line>
                                    </svg>
                                    <span class="text-yellow-600">
                                        {{ $user->is_active ? 'Деактивировать' : 'Активировать' }}
                                    </span>
                                </div>
                            </button>
                        </form>

                        <!-- Delete User -->
                        <form action="{{ route('user.destroy', $user) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="w-full text-left px-3 py-2 text-sm rounded-md hover:bg-red-50 transition-colors duration-200"
                                    onclick="return confirm('Удалить пользователя? Это действие нельзя отменить!')">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4 text-red-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                    <span class="text-red-600">Удалить пользователя</span>
                                </div>
                            </button>
                        </form>
                    @else
                        <div class="text-sm text-slate-500 italic">
                            Нельзя изменять собственную учетную запись
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div id="reset-password-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg max-w-md w-full p-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Сброс пароля</h3>
            <form method="POST" action="{{ route('user.reset-password', $user) }}">
                @csrf
                <div class="mb-4">
                    <label for="new_password" class="form-label">Новый пароль</label>
                    <input type="password"
                           id="new_password"
                           name="new_password"
                           class="form-input"
                           placeholder="Минимум 8 символов"
                           required>
                </div>
                <div class="mb-6">
                    <label for="new_password_confirmation" class="form-label">Подтверждение пароля</label>
                    <input type="password"
                           id="new_password_confirmation"
                           name="new_password_confirmation"
                           class="form-input"
                           placeholder="Повторите пароль"
                           required>
                </div>
                <div class="flex items-center justify-end space-x-3">
                    <button type="button"
                            onclick="hideResetPasswordModal()"
                            class="btn-outline">
                        Отмена
                    </button>
                    <button type="submit" class="btn-primary">
                        Сбросить пароль
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function showResetPasswordModal() {
        document.getElementById('reset-password-modal').classList.remove('hidden');
    }

    function hideResetPasswordModal() {
        document.getElementById('reset-password-modal').classList.add('hidden');
    }

    // Ensure modals can be closed with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            hideResetPasswordModal();
        }
    });

    // Закрытие модального окна при клике вне его
    document.getElementById('reset-password-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            hideResetPasswordModal();
        }
    });
</script>
@endpush
@endsection
