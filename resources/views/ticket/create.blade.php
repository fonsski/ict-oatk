@extends('layouts.app')

@section('title', 'Создать заявку - ICT')

@section('content')
<div class="container-width section-padding">
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-slate-900 mb-2">Создать новую заявку</h1>
            <p class="text-slate-600">Опишите вашу проблему, и мы поможем её решить</p>
        </div>

        <!-- Contact Info -->
        @auth
        <div class="card p-6 mb-8">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Контактная информация</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-slate-500">Имя:</span>
                    <span class="text-slate-900 font-medium ml-2">{{ auth()->user()->name }}</span>
                </div>
                <div>
                    <span class="text-slate-500">Email:</span>
                    <span class="text-slate-900 font-medium ml-2">{{ auth()->user()->email }}</span>
                </div>
            </div>
        </div>
        @endauth

        <!-- Ticket Form -->
        <form action="{{ route('tickets.store') }}" method="POST" class="card p-8">
            @csrf
            
            <div class="space-y-6">
                <!-- Title -->
                <div>
                    <label for="title" class="form-label">Название заявки *</label>
                    <input type="text" 
                           id="title" 
                           name="title" 
                           value="{{ old('title') }}" 
                           class="form-input @error('title') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror"
                           placeholder="Кратко опишите проблему"
                           required>
                    @error('title')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Category -->
                <div>
                    <label for="category_id" class="form-label">Категория *</label>
                    <select id="category_id" 
                            name="category_id" 
                            class="form-input @error('category_id') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror"
                            required>
                        <option value="">Выберите категорию</option>
                        @foreach($categories ?? [] as $category)
                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Priority -->
                <div>
                    <label class="form-label">Приоритет *</label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <label class="flex items-center p-4 border border-slate-200 rounded-lg cursor-pointer hover:border-slate-300 transition-colors duration-200">
                            <input type="radio" name="priority" value="low" {{ old('priority') == 'low' ? 'checked' : '' }} class="mr-3 text-blue-600 focus:ring-blue-500">
                            <div>
                                <div class="font-medium text-slate-900">Низкий</div>
                                <div class="text-sm text-slate-500">Обычная проблема</div>
                            </div>
                        </label>
                        
                        <label class="flex items-center p-4 border border-slate-200 rounded-lg cursor-pointer hover:border-slate-300 transition-colors duration-200">
                            <input type="radio" name="priority" value="medium" {{ old('priority') == 'medium' ? 'checked' : '' }} class="mr-3 text-blue-600 focus:ring-blue-500">
                            <div>
                                <div class="font-medium text-slate-900">Средний</div>
                                <div class="text-sm text-slate-500">Важная проблема</div>
                            </div>
                        </label>
                        
                        <label class="flex items-center p-4 border border-slate-200 rounded-lg cursor-pointer hover:border-slate-300 transition-colors duration-200">
                            <input type="radio" name="priority" value="high" {{ old('priority') == 'high' ? 'checked' : '' }} class="mr-3 text-blue-600 focus:ring-blue-500">
                            <div>
                                <div class="font-medium text-slate-900">Высокий</div>
                                <div class="text-sm text-slate-500">Критическая проблема</div>
                            </div>
                        </label>
                    </div>
                    @error('priority')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="form-label">Описание проблемы *</label>
                    <textarea id="description" 
                              name="description" 
                              rows="6" 
                              class="form-input @error('description') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror"
                              placeholder="Подробно опишите проблему, что происходит, когда возникла, какие ошибки видите"
                              required>{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Location -->
                <div>
                    <label for="location_id" class="form-label">Местоположение</label>
                    <select id="location_id" 
                            name="location_id" 
                            class="form-input @error('location_id') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror">
                        <option value="">Выберите местоположение</option>
                        @foreach($locations ?? [] as $location)
                            <option value="{{ $location->id }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>
                                {{ $location->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('location_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex flex-col sm:flex-row gap-4 mt-8 pt-6 border-t border-slate-200">
                <button type="submit" class="btn-primary flex-1">
                    Создать заявку
                </button>
                <a href="{{ route('tickets.index') }}" class="btn-outline flex-1 text-center">
                    Отмена
                </a>
            </div>
        </form>

        <!-- Help Section -->
        <div class="mt-8 text-center">
            <p class="text-slate-600 mb-4">Нужна помощь?</p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="{{ route('knowledge.index') }}" class="text-blue-600 hover:text-blue-700 font-medium">
                    База знаний
                </a>
                <span class="text-slate-300">•</span>
                <a href="{{ route('tickets.index') }}" class="text-blue-600 hover:text-blue-700 font-medium">
                    Мои заявки
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
