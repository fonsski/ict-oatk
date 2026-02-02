@extends('layouts.app')

@section('title', 'Создать кабинет - ICT')

@section('content')
<div class="max-w-4xl mx-auto px-6 py-8">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 mb-2">Создать кабинет</h1>
            <p class="text-slate-600">Добавить новый кабинет в систему</p>
        </div>
        <a href="{{ route('room.index') }}"
            class="inline-flex items-center px-4 py-2 text-slate-600 hover:text-slate-900 transition-colors duration-200">
            <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Назад к списку
        </a>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow-sm border border-slate-200">
        <form action="{{ route('room.store') }}" method="POST" class="p-6">
            @csrf

            <div class="space-y-6">
                <!-- Basic Information -->
                <div>
                    <h3 class="text-lg font-medium text-slate-900 mb-4">Основная информация</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Number -->
                        <div>
                            <label for="number" class="block text-sm font-medium text-slate-700 mb-2">
                                Номер кабинета <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   id="number"
                                   name="number"
                                   value="{{ old('number') }}"
                                   class="block w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('number') border-red-300 @enderror"
                                   placeholder="например: 101, А-205"
                                   required>
                            @error('number')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-slate-700 mb-2">
                                Название <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   id="name"
                                   name="name"
                                   value="{{ old('name') }}"
                                   maxlength="100"
                                   minlength="2"
                                   data-char-counter
                                   data-max-length="100"
                                   data-min-length="2"
                                   data-warning-threshold="80"
                                   data-help-text="Минимум 2, максимум 100 символов"
                                   class="block w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-300 @enderror"
                                   placeholder="Название кабинета"
                                   required>
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Location -->
                <div>
                    <h3 class="text-lg font-medium text-slate-900 mb-4">Местоположение</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Building -->
                        <div>
                            <label for="building" class="block text-sm font-medium text-slate-700 mb-2">
                                Здание
                            </label>
                            <input type="text"
                                   id="building"
                                   name="building"
                                   value="{{ old('building') }}"
                                   list="buildings"
                                   class="block w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('building') border-red-300 @enderror"
                                   placeholder="например: Главный корпус">
                            <datalist id="buildings">
                                @foreach($buildings as $building)
                                    <option value="{{ $building }}">
                                @endforeach
                            </datalist>
                            @error('building')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Floor -->
                        <div>
                            <label for="floor" class="block text-sm font-medium text-slate-700 mb-2">
                                Этаж
                            </label>
                            <input type="text"
                                   id="floor"
                                   name="floor"
                                   value="{{ old('floor') }}"
                                   class="block w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('floor') border-red-300 @enderror"
                                   placeholder="например: 1, 2, подвал">
                            @error('floor')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Capacity -->
                        <div>
                            <label for="capacity" class="block text-sm font-medium text-slate-700 mb-2">
                                Вместимость
                            </label>
                            <input type="number"
                                   id="capacity"
                                   name="capacity"
                                   value="{{ old('capacity') }}"
                                   min="1"
                                   max="1000"
                                   class="block w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('capacity') border-red-300 @enderror"
                                   placeholder="количество мест">
                            @error('capacity')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Type and Status -->
                <div>
                    <h3 class="text-lg font-medium text-slate-900 mb-4">Тип и статус</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Type -->
                        <div>
                            <label for="type" class="block text-sm font-medium text-slate-700 mb-2">
                                Тип кабинета <span class="text-red-500">*</span>
                            </label>
                            <select id="type"
                                    name="type"
                                    class="block w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('type') border-red-300 @enderror"
                                    required>
                                <option value="">Выберите тип</option>
                                @foreach($types as $key => $name)
                                    <option value="{{ $key }}" {{ old('type') == $key ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Status -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-slate-700 mb-2">
                                Статус <span class="text-red-500">*</span>
                            </label>
                            <select id="status"
                                    name="status"
                                    class="block w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('status') border-red-300 @enderror"
                                    required>
                                @foreach($statuses as $key => $name)
                                    <option value="{{ $key }}" {{ old('status', 'available') == $key ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-slate-700 mb-2">
                        Описание
                    </label>
                    <textarea id="description"
                              name="description"
                              rows="3"
                              class="block w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('description') border-red-300 @enderror"
                              placeholder="Дополнительная информация о кабинете">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Contact Information -->
                <div>
                    <h3 class="text-lg font-medium text-slate-900 mb-4">Контактная информация</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Responsible Person -->
                        <div>
                            <label for="responsible_person" class="block text-sm font-medium text-slate-700 mb-2">
                                Ответственный
                            </label>
                            <input type="text"
                                   id="responsible_person"
                                   name="responsible_person"
                                   value="{{ old('responsible_person') }}"
                                   class="block w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('responsible_person') border-red-300 @enderror"
                                   placeholder="ФИО ответственного лица">
                            @error('responsible_person')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Phone -->
                        <div>
                            <label for="phone" class="block text-sm font-medium text-slate-700 mb-2">
                                Телефон
                            </label>
                            <input type="tel"
                                   id="phone"
                                   name="phone"
                                   value="{{ old('phone') }}"
                                   class="block w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('phone') border-red-300 @enderror"
                                   placeholder="+7 (xxx) xxx-xx-xx">
                            @error('phone')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div>
                    <label for="notes" class="block text-sm font-medium text-slate-700 mb-2">
                        Примечания
                    </label>
                    <textarea id="notes"
                              name="notes"
                              rows="3"
                              class="block w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('notes') border-red-300 @enderror"
                              placeholder="Дополнительные заметки">{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Settings -->
                <div>
                    <h3 class="text-lg font-medium text-slate-900 mb-4">Настройки</h3>
                    <div class="flex items-center">
                        <input type="checkbox"
                               id="is_active"
                               name="is_active"
                               value="1"
                               {{ old('is_active', true) ? 'checked' : '' }}
                               class="rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500 focus:ring-offset-0">
                        <label for="is_active" class="ml-2 text-sm text-slate-700">
                            Активный (кабинет доступен для использования)
                        </label>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end space-x-4 pt-6 border-t border-slate-200 mt-8">
                <a href="{{ route('room.index') }}"
                    class="px-4 py-2 text-slate-700 hover:text-slate-900 transition-colors duration-200">
                    Отмена
                </a>
                <button type="submit"
                        class="inline-flex items-center px-6 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200">
                    <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17,21 17,13 7,13 7,21"></polyline>
                        <polyline points="7,3 7,8 15,8"></polyline>
                    </svg>
                    Создать кабинет
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
