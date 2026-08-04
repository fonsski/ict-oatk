@extends('layouts.app')

@section('title', 'Списание расходников - ICT Help')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white shadow-md rounded-lg p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Списание расходников</h1>
            <p class="text-sm text-gray-600 mb-6">В одном списании можно указать несколько позиций — по каждой своё количество.</p>

            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('consumable-write-offs.store') }}" method="POST">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label for="written_off_at" class="block text-gray-700 text-sm font-bold mb-2">Дата списания *</label>
                        <input type="date" name="written_off_at" id="written_off_at" required
                               max="{{ now()->format('Y-m-d') }}"
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('written_off_at') border-red-500 @enderror"
                               value="{{ old('written_off_at', now()->format('Y-m-d')) }}">
                        @error('written_off_at')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="reason" class="block text-gray-700 text-sm font-bold mb-2">Причина</label>
                        <input type="text" name="reason" id="reason" maxlength="255"
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                               value="{{ old('reason') }}" placeholder="Например: израсходовано при обслуживании">
                        @error('reason')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="mb-6">
                    <label for="comment" class="block text-gray-700 text-sm font-bold mb-2">Комментарий</label>
                    <textarea name="comment" id="comment" rows="2" maxlength="1000"
                              class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">{{ old('comment') }}</textarea>
                </div>

                <div class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-gray-700 text-sm font-bold">Позиции списания *</label>
                        <button type="button" id="add-item-row" class="btn-secondary text-sm py-1.5">+ Добавить позицию</button>
                    </div>
                    @error('items')<p class="text-red-500 text-xs mb-2">{{ $message }}</p>@enderror

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Расходник</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Остаток</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Кол-во к списанию</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase"></th>
                                </tr>
                            </thead>
                            <tbody id="items-body" class="bg-white divide-y divide-gray-200"></tbody>
                        </table>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Провести списание
                    </button>
                    <a href="{{ route('consumable-write-offs.index') }}" class="text-gray-600 hover:text-gray-800 font-medium">Отмена</a>
                </div>
            </form>
        </div>
    </div>
</div>

<template id="item-row-template">
    <tr class="item-row">
        <td class="px-3 py-2">
            <select name="items[__INDEX__][consumable_id]" required class="consumable-select rounded border-gray-300 px-2 py-1.5 w-full">
                <option value="">— выберите расходник —</option>
                @foreach($consumables as $consumable)
                <option value="{{ $consumable->id }}" data-stock="{{ $consumable->quantity }}" data-unit="{{ $consumable->unit }}">
                    {{ $consumable->name }}
                </option>
                @endforeach
            </select>
        </td>
        <td class="px-3 py-2 whitespace-nowrap stock-cell text-gray-500">—</td>
        <td class="px-3 py-2">
            <input type="number" name="items[__INDEX__][quantity]" min="1" value="1" required class="item-quantity rounded border-gray-300 px-2 py-1.5 w-28">
        </td>
        <td class="px-3 py-2">
            <button type="button" class="remove-item-row text-red-600 hover:text-red-800">Удалить</button>
        </td>
    </tr>
</template>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tbody = document.getElementById('items-body');
    const template = document.getElementById('item-row-template');
    const addButton = document.getElementById('add-item-row');
    const preselected = @json($preselectedConsumableId);
    let index = 0;

    function refreshStock(row) {
        const select = row.querySelector('.consumable-select');
        const option = select.options[select.selectedIndex];
        const stockCell = row.querySelector('.stock-cell');
        const quantityInput = row.querySelector('.item-quantity');

        if (!option || !option.dataset.stock) {
            stockCell.textContent = '—';
            quantityInput.removeAttribute('max');
            return;
        }

        stockCell.textContent = option.dataset.stock + ' ' + option.dataset.unit;
        quantityInput.max = option.dataset.stock;
    }

    function addRow(consumableId) {
        const html = template.innerHTML.replaceAll('__INDEX__', index++);
        const wrapper = document.createElement('tbody');
        wrapper.innerHTML = html;
        const row = wrapper.firstElementChild;
        tbody.appendChild(row);

        if (consumableId) {
            row.querySelector('.consumable-select').value = consumableId;
        }

        refreshStock(row);
        row.querySelector('.consumable-select').addEventListener('change', () => refreshStock(row));
        row.querySelector('.remove-item-row').addEventListener('click', () => row.remove());
    }

    addButton.addEventListener('click', () => addRow(null));
    addRow(preselected);
});
</script>
@endpush
