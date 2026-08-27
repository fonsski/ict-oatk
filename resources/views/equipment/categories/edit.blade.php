@extends('layouts.app')

@section('title', 'Редактировать категорию оборудования')

@section('content')
<div class="max-w-7xl mx-auto px-6 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <h1 class="text-2xl font-bold text-slate-900 mb-6">Редактировать категорию оборудования</h1>

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative mb-4">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('equipment.equipment-categories.update', $category) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label for="name" class="block text-slate-700 text-sm font-bold mb-2">
                        Название категории *
                    </label>
                    <input type="text" name="name" id="name"
                           class="w-full rounded-lg border-slate-300 px-3 py-2 text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           value="{{ old('name', $category->name) }}" 
                           required
                           minlength="2" 
                           maxlength="255"
                           data-char-counter
                           data-max-length="255"
                           data-min-length="2"
                           data-warning-threshold="200"
                           data-help-text="Минимум 2, максимум 255 символов">
                </div>

                <div class="mb-6">
                    <label for="description" class="block text-slate-700 text-sm font-bold mb-2">
                        Описание категории
                    </label>
                    <textarea name="description" id="description" rows="3"
                              class="w-full rounded-lg border-slate-300 px-3 py-2 text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ old('description', $category->description) }}</textarea>
                </div>

                <div class="mb-6">
                    <label class="flex items-center text-slate-700 text-sm font-bold">
                        <input type="hidden" name="has_operating_system" value="0">
                        <input type="checkbox" name="has_operating_system" value="1" class="mr-2"
                               {{ old('has_operating_system', $category->has_operating_system) ? 'checked' : '' }}>
                        Указывать операционную систему
                    </label>
                    <p class="text-xs text-slate-500 mt-1">
                        Включите для ПК, ноутбуков и серверов — в карточке такой техники появится поле выбора ОС
                    </p>
                </div>

                <div class="flex items-center justify-between">
                    <button type="submit"
                            class="btn-primary">
                        Обновить
                    </button>
                    <a href="{{ route('equipment.equipment-categories.index') }}"
                       class="text-slate-600 hover:text-slate-800 font-medium">
                        Отмена
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
