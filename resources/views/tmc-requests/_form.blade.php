{{--
    Общая форма заявки ТМЦ для create/edit.
    Ожидает: необязательно $request (при редактировании).
--}}
@php
    $request = $request ?? null;
    $existingItems = $request ? $request->items->map(fn($i) => [
        'name' => $i->name,
        'quantity' => $i->quantity,
        'unit' => $i->unit,
        'unit_price' => $i->unit_price,
    ])->values() : collect();
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
    <div>
        <label for="number" class="block text-sm font-medium text-slate-700 mb-1">Номер заявки</label>
        <input type="text" name="number" id="number" maxlength="50"
               class="w-full rounded-lg border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
               value="{{ old('number', $request->number ?? '') }}">
    </div>
    <div>
        <label for="date" class="block text-sm font-medium text-slate-700 mb-1">Дата <span class="text-red-500">*</span></label>
        <input type="date" name="date" id="date" required
               class="w-full rounded-lg border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
               value="{{ old('date', $request && $request->date ? $request->date->format('Y-m-d') : now()->format('Y-m-d')) }}">
        @error('date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>
</div>

<div class="mb-6">
    <label class="flex items-center justify-between mb-2">
        <span class="block text-sm font-medium text-slate-700">Позиции <span class="text-red-500">*</span></span>
        <button type="button" id="add-item-row" class="btn-secondary text-sm py-1.5">+ Добавить позицию</button>
    </label>
    @error('items')<p class="text-red-500 text-xs mb-2">{{ $message }}</p>@enderror

    <div class="overflow-x-auto rounded-lg border border-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-medium text-slate-500 uppercase">Наименование</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-slate-500 uppercase">Кол-во</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-slate-500 uppercase">Ед. измер.</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-slate-500 uppercase">Цена за ед., руб.</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-slate-500 uppercase">Стоимость, руб.</th>
                    <th class="px-3 py-2"></th>
                </tr>
            </thead>
            <tbody id="items-body" class="bg-white divide-y divide-slate-200"></tbody>
            <tfoot>
                <tr class="bg-slate-50 font-semibold">
                    <td class="px-3 py-2 text-right" colspan="4">Итого:</td>
                    <td class="px-3 py-2" id="items-total">0,00</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<div class="mb-6">
    <label for="purpose" class="block text-sm font-medium text-slate-700 mb-1">Обоснование приобретения</label>
    <textarea name="purpose" id="purpose" rows="3" maxlength="2000"
              class="w-full rounded-lg border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="Например: Необходимо для диагностики и ремонта принтеров колледжа">{{ old('purpose', $request->purpose ?? '') }}</textarea>
    <p class="text-xs text-slate-400 mt-1">Печатается на обороте листа (вторая страница).</p>
</div>

<template id="item-row-template">
    <tr class="item-row">
        <td class="px-3 py-2 align-top">
            <input type="text" name="items[__INDEX__][name]" maxlength="255" required class="item-name w-full rounded-lg border-slate-300 px-2 py-1.5">
        </td>
        <td class="px-3 py-2 align-top">
            <input type="number" name="items[__INDEX__][quantity]" min="1" value="1" required class="item-quantity w-20 rounded-lg border-slate-300 px-2 py-1.5">
        </td>
        <td class="px-3 py-2 align-top">
            <input type="text" name="items[__INDEX__][unit]" maxlength="32" value="шт." class="item-unit w-20 rounded-lg border-slate-300 px-2 py-1.5">
        </td>
        <td class="px-3 py-2 align-top">
            <input type="number" name="items[__INDEX__][unit_price]" min="0" step="0.01" value="0" required class="item-price w-28 rounded-lg border-slate-300 px-2 py-1.5">
        </td>
        <td class="px-3 py-2 align-top text-sm text-slate-700 item-sum whitespace-nowrap">0,00</td>
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
    const totalCell = document.getElementById('items-total');
    let index = 0;

    const fmt = (n) => n.toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function recalcRow(row) {
        const qty = parseFloat(row.querySelector('.item-quantity').value) || 0;
        const price = parseFloat(row.querySelector('.item-price').value) || 0;
        row.querySelector('.item-sum').textContent = fmt(qty * price);
    }

    function recalcTotal() {
        let total = 0;
        tbody.querySelectorAll('.item-row').forEach((row) => {
            const qty = parseFloat(row.querySelector('.item-quantity').value) || 0;
            const price = parseFloat(row.querySelector('.item-price').value) || 0;
            total += qty * price;
        });
        totalCell.textContent = fmt(total);
    }

    function addRow(prefill) {
        const html = template.innerHTML.replaceAll('__INDEX__', index++);
        const wrapper = document.createElement('tbody');
        wrapper.innerHTML = html;
        const row = wrapper.firstElementChild;
        tbody.appendChild(row);

        if (prefill) {
            row.querySelector('.item-name').value = prefill.name;
            row.querySelector('.item-quantity').value = prefill.quantity;
            if (prefill.unit) row.querySelector('.item-unit').value = prefill.unit;
            row.querySelector('.item-price').value = prefill.unit_price;
        }

        recalcRow(row);
        recalcTotal();
        row.querySelector('.item-quantity').addEventListener('input', () => { recalcRow(row); recalcTotal(); });
        row.querySelector('.item-price').addEventListener('input', () => { recalcRow(row); recalcTotal(); });
        row.querySelector('.remove-item-row').addEventListener('click', () => { row.remove(); recalcTotal(); });
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
