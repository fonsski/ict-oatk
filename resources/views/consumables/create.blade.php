@extends('layouts.app')

@section('title', 'Добавить расходник - ICT Help')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white shadow-md rounded-lg p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Добавить расходник</h1>

            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('consumables.store') }}" method="POST">
                @csrf

                <div class="mb-4">
                    <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Название *</label>
                    <input type="text" name="name" id="name"
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('name') border-red-500 @enderror"
                           value="{{ old('name') }}" required minlength="2" maxlength="255"
                           placeholder="Например: Картридж HP CF226A">
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="category" class="block text-gray-700 text-sm font-bold mb-2">Категория</label>
                    <input type="text" name="category" id="category"
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('category') border-red-500 @enderror"
                           value="{{ old('category') }}" maxlength="255"
                           placeholder="Например: Картриджи">
                    @error('category')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-3 gap-4 mb-4">
                    <div>
                        <label for="quantity" class="block text-gray-700 text-sm font-bold mb-2">Начальный остаток *</label>
                        <input type="number" name="quantity" id="quantity" min="0"
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('quantity') border-red-500 @enderror"
                               value="{{ old('quantity', 0) }}" required>
                        @error('quantity')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="min_quantity" class="block text-gray-700 text-sm font-bold mb-2">Мин. остаток</label>
                        <input type="number" name="min_quantity" id="min_quantity" min="0"
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('min_quantity') border-red-500 @enderror"
                               value="{{ old('min_quantity') }}">
                        @error('min_quantity')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="unit" class="block text-gray-700 text-sm font-bold mb-2">Ед. измерения</label>
                        <input type="text" name="unit" id="unit" maxlength="20"
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('unit') border-red-500 @enderror"
                               value="{{ old('unit', 'шт') }}">
                        @error('unit')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mb-4">
                    <label for="room_id" class="block text-gray-700 text-sm font-bold mb-2">Место хранения (кабинет)</label>
                    <select name="room_id" id="room_id"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('room_id') border-red-500 @enderror">
                        <option value="">Не указано</option>
                        @foreach($rooms as $room)
                            <option value="{{ $room->id }}" {{ old('room_id') == $room->id ? 'selected' : '' }}>
                                {{ $room->display_label }}
                            </option>
                        @endforeach
                    </select>
                    @error('room_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="responsible_user_id" class="block text-gray-700 text-sm font-bold mb-2">Ответственный</label>
                    <select name="responsible_user_id" id="responsible_user_id"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('responsible_user_id') border-red-500 @enderror">
                        <option value="">Не указан</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('responsible_user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-sm text-gray-500 mt-1">Определяет, за каким отделом/мастером числится расходник</p>
                    @error('responsible_user_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6">
                    <label for="notes" class="block text-gray-700 text-sm font-bold mb-2">Примечания</label>
                    <textarea name="notes" id="notes" rows="3" maxlength="1000"
                              class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('notes') border-red-500 @enderror">{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between">
                    <button type="submit"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Сохранить
                    </button>
                    <a href="{{ route('consumables.index') }}" class="text-gray-600 hover:text-gray-800 font-medium">
                        Отмена
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
