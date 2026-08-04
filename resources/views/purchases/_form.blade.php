{{--
    Общая форма закупки для create.blade.php и edit.blade.php.
    Ожидает: $consumables, $categories, необязательно $purchase (при редактировании).
--}}
@php
    $purchase = $purchase ?? null;
    $existingItems = $purchase ? $purchase->items->map(fn($i) => [
        'item_type' => $i->item_type,
        'consumable_id' => $i->consumable_id,
        'equipment_category_id' => $i->equipment_category_id,
        'name' => $i->name,
        'quantity' => $i->quantity,
        'unit_price' => $i->unit_price,
    ])->values() : collect();
@endphp

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div>
        <label for="number" class="block text-gray-700 text-sm font-bold mb-2">Номер закупки *</label>
        <input type="text" name="number" id="number" maxlength="50" required
               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('number') border-red-500 @enderror"
               value="{{ old('number', $purchase->number ?? '') }}">
        @error('number')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="date" class="block text-gray-700 text-sm font-bold mb-2">Дата *</label>
        <input type="date" name="date" id="date" required
               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('date') border-red-500 @enderror"
               value="{{ old('date', $purchase && $purchase->date ? $purchase->date->format('Y-m-d') : now()->format('Y-m-d')) }}">
        @error('date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="supplier" class="block text-gray-700 text-sm font-bold mb-2">Поставщик *</label>
        <input type="text" name="supplier" id="supplier" maxlength="255" required
               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('supplier') border-red-500 @enderror"
               value="{{ old('supplier', $purchase->supplier ?? '') }}">
        @error('supplier')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>
</div>

<div class="mb-6">
    <label for="comment" class="block text-gray-700 text-sm font-bold mb-2">Комментарий</label>
    <textarea name="comment" id="comment" rows="2" maxlength="1000"
              class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">{{ old('comment', $purchase->comment ?? '') }}</textarea>
    @error('comment')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
</div>

<div class="mb-6">
    <div class="flex items-center justify-between mb-2">
        <label class="block text-gray-700 text-sm font-bold">Позиции закупки *</label>
        <button type="button" id="add-item-row" class="btn-secondary text-sm py-1.5">+ Добавить позицию</button>
    </div>
    @error('items')<p class="text-red-500 text-xs mb-2">{{ $message }}</p>@enderror

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Тип</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Расходник / категория оборудования</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Наименование</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Кол-во</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Цена</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase"></th>
                </tr>
            </thead>
            <tbody id="items-body" class="bg-white divide-y divide-gray-200"></tbody>
        </table>
    </div>
    <p class="text-xs text-gray-500 mt-2">Позиция «Оборудование» — при проведении закупки заведёт в инвентарь указанное количество единиц (инвентарный номер проставляется позже вручную). Позиция «Расходник» — увеличит остаток выбранного расходника.</p>
</div>

<template id="item-row-template">
    <tr class="item-row">
        <td class="px-3 py-2 align-top">
            <select name="items[__INDEX__][item_type]" class="item-type rounded border-gray-300 px-2 py-1.5 w-full">
                <option value="consumable">Расходник</option>
                <option value="equipment">Оборудование</option>
            </select>
        </td>
        <td class="px-3 py-2 align-top">
            <select name="items[__INDEX__][consumable_id]" class="consumable-select rounded border-gray-300 px-2 py-1.5 w-full">
                <option value="">— выберите расходник —</option>
                @foreach($consumables as $consumable)
                <option value="{{ $consumable->id }}">{{ $consumable->name }}</option>
                @endforeach
            </select>
            <select name="items[__INDEX__][equipment_category_id]" class="category-select hidden rounded border-gray-300 px-2 py-1.5 w-full">
                <option value="">— категория (необязательно) —</option>
                @foreach($categories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </td>
        <td class="px-3 py-2 align-top">
            <input type="text" name="items[__INDEX__][name]" maxlength="255" required class="item-name rounded border-gray-300 px-2 py-1.5 w-full">
        </td>
        <td class="px-3 py-2 align-top">
            <input type="number" name="items[__INDEX__][quantity]" min="1" value="1" required class="item-quantity rounded border-gray-300 px-2 py-1.5 w-20">
        </td>
        <td class="px-3 py-2 align-top">
            <input type="number" name="items[__INDEX__][unit_price]" min="0" step="0.01" value="0" required class="item-price rounded border-gray-300 px-2 py-1.5 w-28">
        </td>
        <td class="px-3 py-2 align-top">
            <button type="button" class="remove-item-row text-red-600 hover:text-red-800 text-sm">Удалить</button>
        </td>
    </tr>
</template>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tbody = document.getElementById('items-body');
    const template = document.getElementById('item-row-template');
    const addButton = document.getElementById('add-item-row');
    let index = 0;

    function toggleRowFields(row) {
        const type = row.querySelector('.item-type').value;
        row.querySelector('.consumable-select').classList.toggle('hidden', type !== 'consumable');
        row.querySelector('.category-select').classList.toggle('hidden', type !== 'equipment');
    }

    function addRow(prefill) {
        const html = template.innerHTML.replaceAll('__INDEX__', index++);
        const wrapper = document.createElement('tbody');
        wrapper.innerHTML = html;
        const row = wrapper.firstElementChild;
        tbody.appendChild(row);

        if (prefill) {
            row.querySelector('.item-type').value = prefill.item_type;
            if (prefill.consumable_id) row.querySelector('.consumable-select').value = prefill.consumable_id;
            if (prefill.equipment_category_id) row.querySelector('.category-select').value = prefill.equipment_category_id;
            row.querySelector('.item-name').value = prefill.name;
            row.querySelector('.item-quantity').value = prefill.quantity;
            row.querySelector('.item-price').value = prefill.unit_price;
        }

        toggleRowFields(row);
        row.querySelector('.item-type').addEventListener('change', () => toggleRowFields(row));
        row.querySelector('.remove-item-row').addEventListener('click', () => row.remove());
    }

    addButton.addEventListener('click', () => addRow(null));

    const existingItems = @json($existingItems);
    if (existingItems.length > 0) {
        existingItems.forEach(addRow);
    } else {
        addRow(null);
    }
});
</script>
@endpush
