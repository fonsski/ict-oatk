@extends('layouts.app')

@section('title', 'Изменить расходник - ICT Help')

@section('content')
<div class="max-w-7xl mx-auto px-6 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <h1 class="text-2xl font-bold text-slate-900 mb-6">Изменить расходник</h1>

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative mb-4">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('consumables.update', $consumable) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label for="name" class="block text-slate-700 text-sm font-bold mb-2">Название *</label>
                    <input type="text" name="name" id="name"
                           class="w-full rounded-lg border-slate-300 px-3 py-2 text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 @enderror"
                           value="{{ old('name', $consumable->name) }}" required minlength="2" maxlength="255">
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="category" class="block text-slate-700 text-sm font-bold mb-2">Категория</label>
                    <input type="text" name="category" id="category"
                           class="w-full rounded-lg border-slate-300 px-3 py-2 text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('category') border-red-500 @enderror"
                           value="{{ old('category', $consumable->category) }}" maxlength="255">
                    @error('category')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="min_quantity" class="block text-slate-700 text-sm font-bold mb-2">Мин. остаток</label>
                        <input type="number" name="min_quantity" id="min_quantity" min="0"
                               class="w-full rounded-lg border-slate-300 px-3 py-2 text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('min_quantity') border-red-500 @enderror"
                               value="{{ old('min_quantity', $consumable->min_quantity) }}">
                        <p class="text-xs text-slate-500 mt-1">Текущий остаток: {{ $consumable->quantity }} {{ $consumable->unit }} — меняется только через приход/расход</p>
                        @error('min_quantity')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="unit" class="block text-slate-700 text-sm font-bold mb-2">Ед. измерения</label>
                        <input type="text" name="unit" id="unit" maxlength="20"
                               class="w-full rounded-lg border-slate-300 px-3 py-2 text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('unit') border-red-500 @enderror"
                               value="{{ old('unit', $consumable->unit) }}">
                        @error('unit')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mb-4">
                    <label for="room_id" class="block text-slate-700 text-sm font-bold mb-2">Место хранения (кабинет)</label>
                    <select name="room_id" id="room_id"
                            class="w-full rounded-lg border-slate-300 px-3 py-2 text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('room_id') border-red-500 @enderror">
                        <option value="">Не указано</option>
                        @foreach($rooms as $room)
                            <option value="{{ $room->id }}" {{ old('room_id', $consumable->room_id) == $room->id ? 'selected' : '' }}>
                                {{ $room->display_label }}
                            </option>
                        @endforeach
                    </select>
                    @error('room_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="responsible_user_id" class="block text-slate-700 text-sm font-bold mb-2">Ответственный</label>
                    <select name="responsible_user_id" id="responsible_user_id"
                            class="w-full rounded-lg border-slate-300 px-3 py-2 text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('responsible_user_id') border-red-500 @enderror">
                        <option value="">Не указан</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('responsible_user_id', $consumable->responsible_user_id) == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('responsible_user_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6">
                    <label for="notes" class="block text-slate-700 text-sm font-bold mb-2">Примечания</label>
                    <textarea name="notes" id="notes" rows="3" maxlength="1000"
                              class="w-full rounded-lg border-slate-300 px-3 py-2 text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('notes') border-red-500 @enderror">{{ old('notes', $consumable->notes) }}</textarea>
                    @error('notes')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between">
                    <button type="submit"
                            class="btn-primary">
                        Сохранить
                    </button>
                    <a href="{{ route('consumables.show', $consumable) }}" class="text-slate-600 hover:text-slate-800 font-medium">
                        Отмена
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
