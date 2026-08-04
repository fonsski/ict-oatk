@extends('layouts.app')

@section('title', 'Расходник — ' . $consumable->name)

@section('content')
<div class="container-width section-padding">
    <div class="mb-5">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('home') }}" class="text-sm text-slate-500 hover:text-slate-700">Главная</a>
                </li>
                <li>
                    <div class="flex items-center">
                        <span class="mx-2 text-slate-400">/</span>
                        <a href="{{ route('consumables.index') }}" class="text-sm text-slate-500 hover:text-slate-700">Расходники</a>
                    </div>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">
                        <span class="mx-2 text-slate-400">/</span>
                        <span class="text-sm text-slate-500">{{ $consumable->name }}</span>
                    </div>
                </li>
            </ol>
        </nav>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
        {{ session('success') }}
    </div>
    @endif

    @if ($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 mb-2">{{ $consumable->name }}</h1>
            @if($consumable->category)
            <p class="text-slate-600">{{ $consumable->category }}</p>
            @endif
        </div>
        @if(auth()->user()->hasRole(['admin', 'master']))
        <div class="flex gap-2">
            <a href="{{ route('consumables.edit', $consumable) }}" class="btn-secondary">Изменить</a>
            <form action="{{ route('consumables.destroy', $consumable) }}" method="POST"
                  onsubmit="return confirm('Удалить расходник «{{ $consumable->name }}»?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-danger">Удалить</button>
            </form>
        </div>
        @endif
    </div>

    <!-- Сводка -->
    <div class="card p-6 mb-8">
        <dl class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <div>
                <dt class="text-sm font-medium text-slate-500 mb-1">Всего</dt>
                <dd class="text-lg font-semibold text-slate-900">{{ $consumable->quantity_total }} {{ $consumable->unit }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500 mb-1">В наличии</dt>
                <dd class="text-lg font-semibold text-green-700">{{ $consumable->quantity_in_stock }} {{ $consumable->unit }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500 mb-1">Установлено</dt>
                <dd class="text-lg font-semibold text-blue-700">{{ $consumable->quantity_installed }} {{ $consumable->unit }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500 mb-1">Списано</dt>
                <dd class="text-lg font-semibold text-red-700">{{ $consumable->quantity_written_off }} {{ $consumable->unit }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500 mb-1">Ответственный</dt>
                <dd class="text-base text-slate-900">{{ $consumable->responsibleUser->name ?? 'Не указан' }}</dd>
            </div>
            <div class="col-span-2">
                <dt class="text-sm font-medium text-slate-500 mb-1">Документ закупки</dt>
                <dd class="text-base text-slate-900">
                    @if($consumable->hasPurchaseDocument())
                        <a href="{{ route('consumables.purchase-document.download', $consumable) }}" class="text-blue-600 hover:text-blue-800">
                            {{ $consumable->purchase_document_original_name }}
                        </a>
                    @else
                        Не загружен
                    @endif
                </dd>
            </div>
            @if($consumable->notes)
            <div class="col-span-full">
                <dt class="text-sm font-medium text-slate-500 mb-1">Примечания</dt>
                <dd class="text-base text-slate-900 whitespace-pre-line">{{ $consumable->notes }}</dd>
            </div>
            @endif
        </dl>
    </div>

    <!-- Установить в оборудование -->
    @if($consumable->quantity_in_stock > 0)
    <div class="card p-6 mb-8">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Установить в оборудование</h2>
        <form action="{{ route('consumables.install', $consumable) }}" method="POST" class="flex flex-wrap items-end gap-3">
            @csrf
            <div class="relative">
                <label class="block text-sm font-medium text-slate-700 mb-1">Оборудование (инв. номер / название)</label>
                <input type="text" id="equipment-picker-input" autocomplete="off" placeholder="Начните вводить инв. номер..."
                       class="rounded border-gray-300 px-3 py-2 w-72">
                <input type="hidden" name="equipment_id" id="equipment-picker-id" required>
                <div id="equipment-picker-results" class="hidden absolute z-20 mt-1 w-72 bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-y-auto"></div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Количество (в наличии: {{ $consumable->quantity_in_stock }})</label>
                <input type="number" name="quantity" min="1" max="{{ $consumable->quantity_in_stock }}" value="1" required class="rounded border-gray-300 px-3 py-2 w-32">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Дата</label>
                <input type="date" name="installed_at" value="{{ now()->format('Y-m-d') }}" class="rounded border-gray-300 px-3 py-2">
            </div>
            <div class="flex-1 min-w-[10rem]">
                <label class="block text-sm font-medium text-slate-700 mb-1">Примечание</label>
                <input type="text" name="note" maxlength="255" class="rounded border-gray-300 px-3 py-2 w-full">
            </div>
            <button type="submit" class="btn-primary">Установить</button>
        </form>
    </div>
    @endif

    <!-- Списать со склада -->
    @if($consumable->quantity_in_stock > 0)
    <div class="card p-6 mb-8">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Списать со склада</h2>
        <form action="{{ route('consumables.write-off', $consumable) }}" method="POST" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Количество (в наличии: {{ $consumable->quantity_in_stock }})</label>
                <input type="number" name="quantity" min="1" max="{{ $consumable->quantity_in_stock }}" value="1" required class="rounded border-gray-300 px-3 py-2 w-32">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Дата</label>
                <input type="date" name="written_off_at" value="{{ now()->format('Y-m-d') }}" class="rounded border-gray-300 px-3 py-2">
            </div>
            <div class="flex-1 min-w-[10rem]">
                <label class="block text-sm font-medium text-slate-700 mb-1">Причина</label>
                <input type="text" name="written_off_reason" maxlength="255" class="rounded border-gray-300 px-3 py-2 w-full">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Документ списания *</label>
                <input type="file" name="document" required class="text-sm">
            </div>
            <button type="submit" class="btn-danger">Списать</button>
        </form>
    </div>
    @endif

    <!-- Движения -->
    <div class="card p-6 mb-8">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Куда установлен и что списано</h2>

        @if($consumable->allocations->isEmpty())
        <p class="text-sm text-gray-500">Движений пока нет</p>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Куда</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Кол-во</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Статус</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Дата / кем</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Документ</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Действия</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($consumable->allocations as $allocation)
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap text-sm">
                            @if($allocation->equipment)
                                <a href="{{ route('equipment.show', $allocation->equipment) }}" class="text-blue-600 hover:text-blue-800">
                                    {{ $allocation->equipment->inventory_number }} @if($allocation->equipment->name) — {{ $allocation->equipment->name }} @endif
                                </a>
                            @else
                                <span class="text-gray-500">Склад</span>
                            @endif
                            @if($allocation->note)
                            <div class="text-xs text-gray-400">{{ $allocation->note }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm">{{ $allocation->quantity }} {{ $consumable->unit }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm">
                            @if($allocation->isInstalled())
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Установлен</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Списан</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                            @if($allocation->isInstalled())
                                {{ optional($allocation->installed_at)->format('d.m.Y') }}
                                <div class="text-xs text-gray-400">{{ $allocation->installedByUser->name ?? '' }}</div>
                            @else
                                {{ optional($allocation->written_off_at)->format('d.m.Y') }}
                                <div class="text-xs text-gray-400">{{ $allocation->writtenOffByUser->name ?? '' }}</div>
                                @if($allocation->written_off_reason)
                                <div class="text-xs text-gray-400">{{ $allocation->written_off_reason }}</div>
                                @endif
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm">
                            @if($allocation->hasWriteOffDocument())
                                <a href="{{ route('consumables.allocations.document.download', [$consumable, $allocation]) }}" class="text-blue-600 hover:text-blue-800">
                                    {{ $allocation->write_off_document_original_name }}
                                </a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm">
                            @if($allocation->isInstalled())
                                <button type="button" class="text-red-600 hover:text-red-800 mr-3" onclick="document.getElementById('write-off-allocation-{{ $allocation->id }}').classList.toggle('hidden')">
                                    Списать
                                </button>
                                <form action="{{ route('consumables.allocations.destroy', [$consumable, $allocation]) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Отменить установку? Количество вернётся на склад.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-gray-500 hover:text-gray-700">Отменить</button>
                                </form>

                                <div id="write-off-allocation-{{ $allocation->id }}" class="hidden mt-2">
                                    <form action="{{ route('consumables.allocations.write-off', [$consumable, $allocation]) }}" method="POST" enctype="multipart/form-data" class="flex flex-wrap items-end gap-2">
                                        @csrf
                                        <div>
                                            <label class="block text-xs text-gray-500">Дата</label>
                                            <input type="date" name="written_off_at" value="{{ now()->format('Y-m-d') }}" class="rounded border-gray-300 px-2 py-1 text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500">Причина</label>
                                            <input type="text" name="written_off_reason" maxlength="255" class="rounded border-gray-300 px-2 py-1 text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500">Документ *</label>
                                            <input type="file" name="document" required class="text-xs">
                                        </div>
                                        <button type="submit" class="btn-danger text-sm py-1">Списать</button>
                                    </form>
                                </div>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    <a href="{{ route('consumables.index') }}" class="btn-outline">
        Вернуться к списку
    </a>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('equipment-picker-input');
    const hiddenId = document.getElementById('equipment-picker-id');
    const results = document.getElementById('equipment-picker-results');
    if (!input) return;

    let timeout;

    input.addEventListener('input', function () {
        hiddenId.value = '';
        clearTimeout(timeout);
        const q = input.value.trim();
        if (q.length < 1) {
            results.classList.add('hidden');
            results.innerHTML = '';
            return;
        }
        timeout = setTimeout(function () {
            fetch('{{ route('equipment.picker') }}?q=' + encodeURIComponent(q))
                .then(response => response.json())
                .then(data => {
                    results.innerHTML = '';
                    if (!data.data || data.data.length === 0) {
                        results.classList.add('hidden');
                        return;
                    }
                    data.data.forEach(function (item) {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'block w-full text-left px-3 py-2 text-sm hover:bg-gray-100';
                        btn.textContent = item.inventory_number + (item.name ? ' — ' + item.name : '');
                        btn.addEventListener('click', function () {
                            input.value = item.inventory_number + (item.name ? ' — ' + item.name : '');
                            hiddenId.value = item.id;
                            results.classList.add('hidden');
                        });
                        results.appendChild(btn);
                    });
                    results.classList.remove('hidden');
                });
        }, 250);
    });

    document.addEventListener('click', function (e) {
        if (!results.contains(e.target) && e.target !== input) {
            results.classList.add('hidden');
        }
    });
});
</script>
@endpush
