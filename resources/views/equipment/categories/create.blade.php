@extends('layouts.app')

@section('title', 'Добавить категорию оборудования')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white shadow-md rounded-lg p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Добавить категорию оборудования</h1>

            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('equipment.equipment-categories.store') }}" method="POST">
                @csrf

                <div class="mb-4">
                    <label for="name" class="block text-gray-700 text-sm font-bold mb-2">
                        Название категории *
                    </label>
                    <input type="text" name="name" id="name"
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('name') border-red-500 @enderror"
                           value="{{ old('name') }}" 
                           required 
                           minlength="2" 
                           maxlength="255"
                           data-char-counter
                           data-max-length="255"
                           data-min-length="2"
                           data-warning-threshold="200"
                           data-help-text="Минимум 2, максимум 255 символов">
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6">
                    <label for="description" class="block text-gray-700 text-sm font-bold mb-2">
                        Описание категории
                    </label>
                    <textarea name="description" id="description" rows="3"
                              class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('description') border-red-500 @enderror"
                              minlength="5" maxlength="1000">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between">
                    <button type="submit"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Сохранить
                    </button>
                    <a href="{{ route('equipment.equipment-categories.index') }}"
                       class="text-gray-600 hover:text-gray-800 font-medium">
                        Отмена
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
