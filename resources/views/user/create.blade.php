@extends('layouts.app')

@section('title', 'Создание пользователя - ICT')

@section('content')
<div class="container-width section-padding">
    <!-- Header -->
            <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-slate-900 mb-2">Создание пользователя</h1>
                <p class="text-slate-600">Добавление новой учетной записи в систему</p>
            </div>
            <a href="{{ route('user.index') }}" class="btn-outline">
                Назад к списку
            </a>
        </div>

    <!-- Create Form -->
    <div class="max-w-2xl">
        <div class="card p-8">
            <form method="POST" action="{{ route('user.store') }}">
                @csrf

                <!-- Name -->
                <div class="mb-6">
                    <label for="name" class="form-label">Имя пользователя <span class="text-red-500">*</span></label>
                    <input type="text"
                           id="name"
                           name="name"
                           value="{{ old('name') }}"
                           class="form-input @error('name') border-red-500 @enderror"
                           placeholder="Введите полное имя пользователя"
                           required>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Телефон -->
                <div class="mb-6">
                    <label for="phone" class="form-label">Номер телефона <span class="text-red-500">*</span></label>
                    <input type="tel"
                           id="phone"
                           name="phone"
                           value="{{ old('phone') }}"
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
                            <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                {{ $role->name }} - {{ $role->description }}
                            </option>
                        @endforeach
                    </select>
                    @error('role_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div class="mb-6">
                    <label for="password" class="form-label">Пароль <span class="text-red-500">*</span></label>
                    <input type="password"
                           id="password"
                           name="password"
                           class="form-input @error('password') border-red-500 @enderror"
                           placeholder="Минимум 8 символов"
                           required>
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-slate-500">Пароль должен содержать минимум 8 символов</p>
                </div>

                <!-- Password Confirmation -->
                <div class="mb-6">
                    <label for="password_confirmation" class="form-label">Подтверждение пароля <span class="text-red-500">*</span></label>
                    <input type="password"
                           id="password_confirmation"
                           name="password_confirmation"
                           class="form-input"
                           placeholder="Повторите пароль"
                           required>
                </div>

                <!-- Active Status -->
                <div class="mb-8">
                    <label class="flex items-center">
                        <input type="checkbox"
                               name="is_active"
                               value="1"
                               {{ old('is_active', true) ? 'checked' : '' }}
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
                        Создать пользователя
                    </button>
                </div>
            </form>
        </div>

        <!-- Help Information -->
        <div class="card p-6 mt-6 bg-blue-50 border-blue-200">
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
@endsection
