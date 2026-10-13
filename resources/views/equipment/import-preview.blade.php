@extends('layouts.app')

@section('title', 'Проверка импорта - ICT Help')

@php
    use App\Services\EquipmentImport\ImportPlan;

    $actionLabels = [
        ImportPlan::CREATE => 'Создать',
        ImportPlan::UPDATE => 'Обновить',
        ImportPlan::SKIP => 'Пропустить',
    ];
    $okRows = array_values(array_filter($plan->rows, fn ($row) => $row['errors'] === []));
    $shownOk = array_slice($okRows, 0, 100);
@endphp

@section('content')
<div class="max-w-7xl mx-auto px-6 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Проверка импорта</h1>
            <p class="text-sm text-slate-500">Файл: {{ $fileName }}</p>
        </div>
        <a href="{{ route('equipment.import.create') }}" class="text-sm text-slate-500 underline">Загрузить другой файл</a>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <div class="text-2xl font-bold text-green-700">{{ $plan->count(ImportPlan::CREATE) }}</div>
            <div class="text-sm text-slate-500">будет создано</div>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <div class="text-2xl font-bold text-blue-700">{{ $plan->count(ImportPlan::UPDATE) }}</div>
            <div class="text-sm text-slate-500">будет обновлено</div>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <div class="text-2xl font-bold text-slate-700">{{ $plan->count(ImportPlan::SKIP) }}</div>
            <div class="text-sm text-slate-500">пропущено (уже есть)</div>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <div class="text-2xl font-bold {{ $plan->hasErrors() ? 'text-red-700' : 'text-slate-400' }}">{{ count($plan->errorRows()) }}</div>
            <div class="text-sm text-slate-500">строк с ошибками</div>
        </div>
    </div>

    @if ($plan->unknownColumns)
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded mb-4 text-sm">
            Эти колонки системе незнакомы и будут проигнорированы:
            <span class="font-mono">{{ implode(', ', $plan->unknownColumns) }}</span>.
            Если это опечатка в названии, исправьте файл.
        </div>
    @endif

    @if ($plan->hasErrors())
        <div class="bg-red-50 border border-red-200 rounded mb-6">
            <div class="px-4 py-3 border-b border-red-200 text-red-800 font-semibold">
                Импорт невозможен: исправьте ошибки в файле и загрузите его заново. Ничего не записано.
            </div>
            <ul class="divide-y divide-red-100 text-sm">
                @foreach ($plan->errorRows() as $row)
                    <li class="px-4 py-2">
                        <span class="font-semibold text-red-900">Строка {{ $row['line'] }}</span>
                        @if ($row['inventory_number'])
                            <span class="text-red-700">(№ {{ $row['inventory_number'] }})</span>
                        @endif
                        <ul class="list-disc list-inside text-red-800">
                            @foreach ($row['errors'] as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($plan->warningRows())
        <div class="bg-yellow-50 border border-yellow-200 rounded mb-6">
            <div class="px-4 py-3 border-b border-yellow-200 text-yellow-800 font-semibold">Предупреждения (импорт не блокируют)</div>
            <ul class="divide-y divide-yellow-100 text-sm">
                @foreach ($plan->warningRows() as $row)
                    <li class="px-4 py-2 text-yellow-900">
                        <span class="font-semibold">Строка {{ $row['line'] }}:</span>
                        {{ implode(' ', $row['warnings']) }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($okRows)
        <div class="bg-white rounded-lg border border-slate-200 mb-6 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500 border-b border-slate-200">
                        <th class="px-4 py-2">Строка</th>
                        <th class="px-4 py-2">Инв. номер</th>
                        <th class="px-4 py-2">Название</th>
                        <th class="px-4 py-2">Действие</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($shownOk as $row)
                        <tr class="border-b border-slate-100">
                            <td class="px-4 py-2 text-slate-500">{{ $row['line'] }}</td>
                            <td class="px-4 py-2 font-mono">{{ $row['inventory_number'] }}</td>
                            <td class="px-4 py-2">{{ $row['name'] }}</td>
                            <td class="px-4 py-2">{{ $actionLabels[$row['action']] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if (count($okRows) > count($shownOk))
                <p class="px-4 py-2 text-xs text-slate-500">Показаны первые {{ count($shownOk) }} из {{ count($okRows) }} корректных строк.</p>
            @endif
        </div>
    @endif

    @unless ($plan->hasErrors())
        <form action="{{ route('equipment.import.store') }}" method="POST">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Выполнить импорт
            </button>
        </form>
    @endunless
</div>
@endsection
