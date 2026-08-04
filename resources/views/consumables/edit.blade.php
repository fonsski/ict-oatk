@extends('layouts.app')

@section('title', 'Изменить расходник')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white shadow-md rounded-lg p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Изменить расходник</h1>

            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('consumables.update', $consumable) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Название *</label>
                    <input type="text" name="name" id="name"
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('name') border-red-500 @enderror"
                           value="{{ old('name', $consumable->name) }}" required minlength="2" maxlength="255">
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="category" class="block text-gray-700 text-sm font-bold mb-2">Категория</label>
                    <input type="text" name="category" id="category"
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('category') border-red-500 @enderror"
                           value="{{ old('category', $consumable->category) }}" maxlength="255">
                    @error('category')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="quantity_total" class="block text-gray-700 text-sm font-bold mb-2">Количество *</label>
                        <input type="number" name="quantity_total" id="quantity_total" min="{{ $consumable->quantity_installed + $consumable->quantity_written_off }}"
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('quantity_total') border-red-500 @enderror"
                               value="{{ old('quantity_total', $consumable->quantity_total) }}" required>
                        <p class="text-xs text-gray-500 mt-1">Не менее {{ $consumable->quantity_installed + $consumable->quantity_written_off }} (уже установлено/списано)</p>
                        @error('quantity_total')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="unit" class="block text-gray-700 text-sm font-bold mb-2">Ед. измерения</label>
                        <input type="text" name="unit" id="unit" maxlength="20"
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('unit') border-red-500 @enderror"
                               value="{{ old('unit', $consumable->unit) }}">
                        @error('unit')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mb-4">
                    <label for="responsible_user_id" class="block text-gray-700 text-sm font-bold mb-2">Ответственный</label>
                    <select name="responsible_user_id" id="responsible_user_id"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('responsible_user_id') border-red-500 @enderror">
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
                    <label for="notes" class="block text-gray-700 text-sm font-bold mb-2">Примечания</label>
                    <textarea name="notes" id="notes" rows="3" maxlength="1000"
                              class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('notes') border-red-500 @enderror">{{ old('notes', $consumable->notes) }}</textarea>
                    @error('notes')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between">
                    <button type="submit"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Сохранить
                    </button>
                    <a href="{{ route('consumables.show', $consumable) }}" class="text-gray-600 hover:text-gray-800 font-medium">
                        Отмена
                    </a>
                </div>
            </form>
        </div>

        <!-- Документ закупки (отдельно от формы) -->
        <div class="bg-white shadow-md rounded-lg p-6 mt-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Документ закупки</h2>

            @if($consumable->hasPurchaseDocument())
            <div class="flex items-center justify-between py-3 border-b border-gray-200 mb-4">
                <div class="min-w-0">
                    <a href="{{ route('consumables.purchase-document.download', $consumable) }}"
                       class="text-sm font-medium text-blue-600 hover:text-blue-800 truncate block">
                        {{ $consumable->purchase_document_original_name }}
                    </a>
                    <span class="text-xs text-gray-400">{{ $consumable->purchase_document_human_size }}</span>
                </div>
                <form action="{{ route('consumables.purchase-document.destroy', $consumable) }}" method="POST"
                      onsubmit="return confirm('Удалить документ закупки?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-800">Удалить</button>
                </form>
            </div>
            @else
            <p class="text-sm text-gray-500 mb-4">Документ пока не загружен</p>
            @endif

            <form action="{{ route('consumables.update', $consumable) }}" method="POST" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3">
                @csrf
                @method('PUT')
                <input type="hidden" name="name" value="{{ $consumable->name }}">
                <input type="hidden" name="category" value="{{ $consumable->category }}">
                <input type="hidden" name="unit" value="{{ $consumable->unit }}">
                <input type="hidden" name="quantity_total" value="{{ $consumable->quantity_total }}">
                <input type="hidden" name="responsible_user_id" value="{{ $consumable->responsible_user_id }}">
                <input type="hidden" name="notes" value="{{ $consumable->notes }}">
                <input type="file" name="purchase_document" required
                       class="text-sm text-gray-700 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                <button type="submit" class="btn-primary py-2 text-sm">{{ $consumable->hasPurchaseDocument() ? 'Заменить файл' : 'Загрузить файл' }}</button>
            </form>
            <p class="mt-2 text-xs text-gray-500">До 10 МБ. Допустимо: pdf, doc(x), xls(x), ppt(x), txt, csv, zip, изображения.</p>
        </div>
    </div>
</div>
@endsection
