@extends('layouts.app')

@section('title', 'Проведение закупки ' . $purchase->number . ' - ICT Help')

@section('content')
<div class="max-w-7xl mx-auto px-6 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <h1 class="text-2xl font-bold text-slate-900 mb-2">Проведение закупки {{ $purchase->number }}</h1>
            <p class="text-sm text-slate-600 mb-6">
                Проверьте позиции и укажите инвентарные номера, выданные бухгалтерией — по одному на каждую единицу оборудования.
                После проведения оборудование появится в инвентаре, а остатки расходников увеличатся. Действие необратимо.
            </p>

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative mb-4">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('purchases.post', $purchase) }}" method="POST">
                @csrf

                @php $equipmentItems = $purchase->items->where('item_type', \App\Models\PurchaseItem::TYPE_EQUIPMENT); @endphp
                @php $consumableItems = $purchase->items->where('item_type', \App\Models\PurchaseItem::TYPE_CONSUMABLE); @endphp

                @if($consumableItems->isNotEmpty())
                <div class="mb-8">
                    <h2 class="text-lg font-semibold text-slate-900 mb-3">Расходники — пополнение остатка</h2>
                    <div class="overflow-x-auto border border-slate-200 rounded">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-slate-500 uppercase">Расходник</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-slate-500 uppercase">Придёт</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-slate-500 uppercase">Остаток сейчас</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-slate-500 uppercase">Станет</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-200">
                                @foreach($consumableItems as $item)
                                <tr>
                                    <td class="px-3 py-2">{{ $item->name }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap">+{{ $item->quantity }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap">{{ $item->consumable->quantity ?? '—' }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap font-medium">
                                        {{ $item->consumable ? $item->consumable->quantity + $item->quantity : '—' }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                @if($equipmentItems->isNotEmpty())
                <div class="mb-8">
                    <h2 class="text-lg font-semibold text-slate-900 mb-3">Оборудование — инвентарные номера</h2>

                    @foreach($equipmentItems as $item)
                    <div class="border border-slate-200 rounded p-4 mb-4">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <div class="font-medium text-slate-900">{{ $item->name }}</div>
                                <div class="text-xs text-slate-500">
                                    {{ $item->quantity }} ед.
                                    @if($item->equipmentCategory) · категория: {{ $item->equipmentCategory->name }} @endif
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            @for($i = 0; $i < $item->quantity; $i++)
                            <div>
                                <label class="block text-xs text-slate-500 mb-1">Единица {{ $i + 1 }} — инв. номер *</label>
                                <input type="text" name="inventory_numbers[{{ $item->id }}][]" required
                                       inputmode="numeric" pattern="\d+" maxlength="20"
                                       placeholder="Например: 2101344347"
                                       value="{{ old('inventory_numbers.' . $item->id . '.' . $i) }}"
                                       class="w-full rounded-lg border-slate-300 px-3 py-2 @error('inventory_numbers.' . $item->id . '.' . $i) border-red-500 @enderror">
                                @error('inventory_numbers.' . $item->id . '.' . $i)
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            @endfor
                        </div>
                    </div>
                    @endforeach

                    <p class="text-xs text-slate-500">
                        Номер выдаётся бухгалтерией колледжа, содержит только цифры и должен быть уникальным в системе.
                    </p>
                </div>
                @endif

                <div class="flex items-center justify-between">
                    <button type="submit" class="btn-primary">
                        Провести закупку
                    </button>
                    <a href="{{ route('purchases.show', $purchase) }}" class="text-slate-600 hover:text-slate-800 font-medium">Отмена</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
