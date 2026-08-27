@extends('layouts.app')

@section('title', 'Расходник — ' . $consumable->name . ' - ICT Help')

@section('content')
<div class="container-width section-padding">
    <div class="mb-5">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li><a href="{{ route('home') }}" class="text-sm text-slate-500 hover:text-slate-700">Главная</a></li>
                <li><span class="mx-2 text-slate-400">/</span><a href="{{ route('consumables.index') }}" class="text-sm text-slate-500 hover:text-slate-700">Расходники</a></li>
                <li><span class="mx-2 text-slate-400">/</span><span class="text-sm text-slate-500">{{ $consumable->name }}</span></li>
            </ol>
        </nav>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded relative mb-4">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative mb-4">
        <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
    @endif

    @if($consumable->isLowStock())
    <div class="bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded relative mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        Остаток ({{ $consumable->quantity }} {{ $consumable->unit }}) достиг минимального порога ({{ $consumable->min_quantity }} {{ $consumable->unit }}). Пора пополнить запас.
    </div>
    @endif

    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 mb-2">{{ $consumable->name }}</h1>
            @if($consumable->category)
            <p class="text-slate-600">{{ $consumable->category }}</p>
            @endif
        </div>
        <div class="flex gap-2">
            <a href="{{ route('consumable-write-offs.create', ['consumable_id' => $consumable->id]) }}" class="btn-secondary">Списать</a>
            @if(auth()->user()->hasRole(['admin', 'master']))
            <a href="{{ route('consumables.edit', $consumable) }}" class="btn-secondary">Изменить</a>
            <form action="{{ route('consumables.destroy', $consumable) }}" method="POST"
                  onsubmit="return confirm('Удалить расходник «{{ $consumable->name }}»?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-danger">Удалить</button>
            </form>
            @endif
        </div>
    </div>

    <div class="card p-6 mb-8">
        <dl class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <div>
                <dt class="text-sm font-medium text-slate-500 mb-1">Текущий остаток</dt>
                <dd class="text-lg font-semibold {{ $consumable->isLowStock() ? 'text-red-700' : 'text-green-700' }}">
                    {{ $consumable->quantity }} {{ $consumable->unit }}
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500 mb-1">Минимальный остаток</dt>
                <dd class="text-lg font-semibold text-slate-900">
                    {{ $consumable->min_quantity !== null ? $consumable->min_quantity . ' ' . $consumable->unit : '—' }}
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500 mb-1">Место хранения</dt>
                <dd class="text-base text-slate-900">{{ $consumable->room->display_label ?? 'Не указано' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500 mb-1">Ответственный</dt>
                <dd class="text-base text-slate-900">{{ $consumable->responsibleUser->name ?? 'Не указан' }}</dd>
            </div>
            @if($consumable->notes)
            <div class="col-span-full">
                <dt class="text-sm font-medium text-slate-500 mb-1">Примечания</dt>
                <dd class="text-base text-slate-900 whitespace-pre-line">{{ $consumable->notes }}</dd>
            </div>
            @endif
        </dl>
    </div>

    @if($consumable->quantity > 0)
    <div class="card p-6 mb-8">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Выдать в оборудование</h2>
        <form action="{{ route('consumables.issue', $consumable) }}" method="POST" class="flex flex-wrap items-end gap-3">
            @csrf
            <div class="relative">
                <label class="block text-sm font-medium text-slate-700 mb-1">Оборудование (инв. номер / название)</label>
                <input type="text" id="equipment-picker-input" autocomplete="off" placeholder="Начните вводить инв. номер..."
                       class="rounded-lg border-slate-300 px-3 py-2 w-72">
                <input type="hidden" name="equipment_id" id="equipment-picker-id" required>
                <div id="equipment-picker-results" class="hidden absolute z-20 mt-1 w-72 bg-white border border-slate-200 rounded-md shadow-lg max-h-56 overflow-y-auto"></div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Количество (в наличии: {{ $consumable->quantity }})</label>
                <input type="number" name="quantity" min="1" max="{{ $consumable->quantity }}" value="1" required class="rounded-lg border-slate-300 px-3 py-2 w-32">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Дата</label>
                <input type="date" name="moved_at" value="{{ now()->format('Y-m-d') }}" class="rounded-lg border-slate-300 px-3 py-2">
            </div>
            <div class="flex-1 min-w-[10rem]">
                <label class="block text-sm font-medium text-slate-700 mb-1">Причина / примечание</label>
                <input type="text" name="reason" maxlength="255" class="rounded-lg border-slate-300 px-3 py-2 w-full">
            </div>
            <button type="submit" class="btn-primary">Выдать</button>
        </form>
    </div>
    @endif

    <div class="card p-6 mb-8">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Движения остатка</h2>

        @if($consumable->movements->isEmpty())
        <p class="text-sm text-slate-500">Движений пока нет</p>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 uppercase">Дата</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 uppercase">Тип</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 uppercase">Кол-во</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 uppercase">Основание</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 uppercase">Кем</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 uppercase">Действия</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @foreach($consumable->movements as $movement)
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $movement->moved_at->format('d.m.Y') }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if($movement->isIncome())
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Приход</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Расход</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $movement->isIncome() ? '+' : '−' }}{{ $movement->quantity }} {{ $consumable->unit }}</td>
                        <td class="px-4 py-3">
                            {{ $movement->reason ?: '—' }}
                            @if($movement->equipment)
                            <div class="text-xs text-slate-400">
                                Оборудование:
                                <a href="{{ route('equipment.show', $movement->equipment) }}" class="text-blue-600 hover:text-blue-800">
                                    {{ $movement->equipment->inventory_number }}
                                </a>
                            </div>
                            @endif
                            @if($movement->purchase)
                            <div class="text-xs text-slate-400">
                                Закупка:
                                <a href="{{ route('purchases.show', $movement->purchase) }}" class="text-blue-600 hover:text-blue-800">
                                    {{ $movement->purchase->number }}
                                </a>
                            </div>
                            @endif
                            @if($movement->consumableWriteOff)
                            <div class="text-xs text-slate-400">
                                Акт списания:
                                <a href="{{ route('consumable-write-offs.show', $movement->consumableWriteOff) }}" class="text-blue-600 hover:text-blue-800">
                                    {{ $movement->consumableWriteOff->number }}
                                </a>
                            </div>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ $movement->movedByUser->name ?? '—' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if($movement->isOutcome() && !$movement->consumable_write_off_id)
                            <form action="{{ route('consumables.movements.destroy', [$consumable, $movement]) }}" method="POST"
                                  onsubmit="return confirm('Отменить выдачу? Количество вернётся на склад.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-slate-500 hover:text-slate-700">Отменить</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    @include('documents.partials.attach-list', [
        'documentable' => $consumable,
        'documentTypeSlug' => 'consumable',
        'documents' => $consumable->documents,
    ])

    <a href="{{ route('consumables.index') }}" class="btn-outline">Вернуться к списку</a>
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
                        btn.className = 'block w-full text-left px-3 py-2 text-sm hover:bg-slate-100';
                        btn.textContent = item.inventory_number + (item.name ? ' — ' + item.name : '');
                        btn.addEventListener('click', function () {
                            input.value = btn.textContent;
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
