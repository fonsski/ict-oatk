@extends('layouts.app')

@section('title', 'Редактирование пользователя - ICT')

@section('content')
<div class="container-width section-padding">
    <!-- Header -->
            <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-slate-900 mb-2">Редактирование пользователя</h1>
                <p class="text-slate-600">Изменение данных учетной записи {{ $user->name }}</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('user.show', $user) }}" class="btn-outline">
                    Просмотр
                </a>
                <a href="{{ route('user.index') }}" class="btn-secondary">
                    Назад к списку
                </a>
            </div>
        </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Edit Form -->
        <div class="lg:col-span-2">
            <div class="card p-8">
                <form method="POST" action="{{ route('user.update', $user) }}">
                    @csrf
                    @method('PUT')

                    <!-- Name -->
                    <div class="mb-6">
                        <label for="name" class="form-label">Имя пользователя <span class="text-red-500">*</span></label>
                        <input type="text"
                               id="name"
                               name="name"
                               value="{{ old('name', $user->name) }}"
                               class="form-input @error('name') border-red-500 @enderror"
                               placeholder="Введите полное имя пользователя"
                               required>
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Phone -->
                    <div class="mb-6">
                        <label for="phone" class="form-label">Номер телефона <span class="text-red-500">*</span></label>
                        <input type="tel"
                               id="phone"
                               name="phone"
                               value="{{ old('phone', $user->phone) }}"
                               class="form-input @error('phone') border-red-500 @enderror"
                               placeholder="+7XXXXXXXXXX"
                               required>
                        @error('phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Role -->
                    <div class="mb-6">
                        <label for="role_id" class="form-label">Роль в системе <span class="text-red-500">*</span></label>
                        <select id="role_id"
                                name="role_id"
                                class="form-input @error('role_id') border-red-500 @enderror"
                                required>
                            <option value="">Выберите роль</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>
                                    {{ $role->name }} - {{ $role->description }}
                                </option>
                            @endforeach
                        </select>
                        @error('role_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Active Status -->
                    <div class="mb-8">
                        <label class="flex items-center">
                            <input type="checkbox"
                                   name="is_active"
                                   value="1"
                                   {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                                   class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-slate-700">Активная учетная запись</span>
                        </label>
                        <p class="mt-1 text-sm text-slate-500">Неактивные пользователи не могут входить в систему</p>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="flex items-center justify-end space-x-4">
                        <a href="{{ route('user.index') }}" class="btn-outline">
                            Отмена
                        </a>
                        <button type="submit" class="btn-primary">
                            Сохранить изменения
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- User Info Card -->
            <div class="card p-6">
                <h3 class="text-lg font-semibold text-slate-900 mb-4">Информация о пользователе</h3>
                <div class="space-y-3">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white text-lg font-semibold">
                            {{ substr($user->name, 0, 1) }}
                        </div>
                        <div>
                            <div class="font-medium text-slate-900">{{ $user->name }}</div>
                            <div class="text-sm text-slate-600">{{ $user->phone }}</div>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-200">
                        <div class="text-sm text-slate-600 mb-1">Роль:</div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ $user->role ? $user->role->name : 'Не назначена' }}
                        </span>
                    </div>

                    <div class="pt-3 border-t border-slate-200">
                        <div class="text-sm text-slate-600 mb-1">Статус:</div>
                        @if($user->is_active)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Активен
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                Неактивен
                            </span>
                        @endif
                    </div>

                    <div class="pt-3 border-t border-slate-200">
                        <div class="text-sm text-slate-600 mb-1">Дата регистрации:</div>
                        <div class="text-sm text-slate-900">{{ $user->created_at->format('d.m.Y H:i') }}</div>
                    </div>

                    @if($user->last_login_at)
                        <div class="pt-3 border-t border-slate-200">
                            <div class="text-sm text-slate-600 mb-1">Последний вход:</div>
                            <div class="text-sm text-slate-900">{{ $user->last_login_at->format('d.m.Y H:i') }}</div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card p-6">
                <h3 class="text-lg font-semibold text-slate-900 mb-4">Быстрые действия</h3>
                <div class="space-y-3">
                    @if($user->id !== auth()->id())
                        <!-- Активация с отправкой email -->
                        @if(!$user->is_active)
                        <form action="{{ route('user.activate', $user) }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="w-full text-left px-3 py-2 text-sm rounded-md hover:bg-green-50 transition-colors duration-200"
                                    onclick="return confirm('Активировать учетную запись? Пользователю будет отправлено письмо с данными для входа.')">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4 text-green-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="8.5" cy="7" r="4"></circle>
                                        <path d="M20 8v6"></path>
                                        <path d="M23 11h-6"></path>
                                    </svg>
                                    <span class="text-green-600">
                                        Активировать с отправкой email
                                    </span>
                                </div>
                            </button>
                        </form>
                        @else
                        <!-- Деактивация -->
                        <form action="{{ route('user.deactivate', $user) }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="w-full text-left px-3 py-2 text-sm rounded-md hover:bg-yellow-50 transition-colors duration-200"
                                    onclick="return confirm('Деактивировать учетную запись? Пользователь не сможет войти в систему.')">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4 text-yellow-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="8.5" cy="7" r="4"></circle>
                                        <line x1="18" y1="8" x2="23" y2="13"></line>
                                        <line x1="23" y1="8" x2="18" y2="13"></line>
                                    </svg>
                                    <span class="text-yellow-600">
                                        Деактивировать учетную запись
                                    </span>
                                </div>
                            </button>
                        </form>

                        <!-- Повторная отправка данных для входа -->
                        <form action="{{ route('user.resend-activation', $user) }}" method="POST" class="mt-2">
                            @csrf
                            <button type="submit"
                                    class="w-full text-left px-3 py-2 text-sm rounded-md hover:bg-blue-50 transition-colors duration-200"
                                    onclick="return confirm('Отправить новые данные для входа на email пользователя? Текущий пароль будет сброшен.')">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 2v4h-4"></path>
                                        <path d="M1 11a9 9 0 0 1 9-9h7.5"></path>
                                        <polyline points="9 15 13 19 9 23"></polyline>
                                        <path d="M16 13H3"></path>
                                    </svg>
                                    <span class="text-blue-600">
                                        Отправить данные для входа
                                    </span>
                                </div>
                            </button>
                        </form>
                        @endif

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

            <!-- Help Information -->
            <div class="card p-6 bg-blue-50 border-blue-200">
                <h3 class="text-lg font-semibold text-blue-900 mb-3">Информация о ролях</h3>
                <div class="space-y-3">
                    @foreach($roles as $role)
                        <div class="flex items-start space-x-3">
                            <div class="w-2 h-2 bg-blue-500 rounded-full mt-2 flex-shrink-0"></div>
                            <div>
                                <div class="font-medium text-blue-900">{{ $role->name }}</div>
                                <div class="text-sm text-blue-700">{{ $role->description }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
