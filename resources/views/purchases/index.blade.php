@extends('layouts.app')

@section('title', 'Закупки - ICT Help')

@section('content')
<div class="max-w-7xl mx-auto px-6 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-slate-900">Закупки</h1>

        @if(auth()->user()->hasRole(['admin', 'master']))
        <a href="{{ route('purchases.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            Новая закупка
        </a>
        @endif
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded relative mb-4">
        {{ session('success') }}
    </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 border-b">
            <form method="GET" class="flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-xs text-slate-500 mb-1">Поставщик</label>
                    <input type="text" name="supplier" value="{{ request('supplier') }}" class="rounded-lg border-slate-300 px-3 py-2" />
                </div>
                <div>
                    <label class="block text-xs text-slate-500 mb-1">Статус</label>
                    <select name="status" class="rounded-lg border-slate-300 px-3 py-2">
                        <option value="">Все статусы</option>
                        @foreach(\App\Models\Purchase::STATUSES as $value => $label)
                        <option value="{{ $value }}" {{ request('status') == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-slate-500 mb-1">С даты</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="rounded-lg border-slate-300 px-3 py-2" />
                </div>
                <div>
                    <label class="block text-xs text-slate-500 mb-1">По дату</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="rounded-lg border-slate-300 px-3 py-2" />
                </div>
                <div>
                    <label class="block text-xs text-slate-500 mb-1">Сумма от</label>
                    <input type="number" step="0.01" name="sum_from" value="{{ request('sum_from') }}" class="rounded-lg border-slate-300 px-3 py-2 w-28" />
                </div>
                <div>
                    <label class="block text-xs text-slate-500 mb-1">Сумма до</label>
                    <input type="number" step="0.01" name="sum_to" value="{{ request('sum_to') }}" class="rounded-lg border-slate-300 px-3 py-2 w-28" />
                </div>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">Применить</button>
                <a href="{{ route('purchases.index') }}" class="text-sm text-slate-500 underline">Сбросить</a>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Номер</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Дата</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Поставщик</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Сумма</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Статус</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Действия</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @forelse($purchases as $purchase)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">{{ $purchase->number }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700">{{ $purchase->date->format('d.m.Y') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700">{{ $purchase->supplier }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700">{{ number_format($purchase->total_sum, 2, ',', ' ') }} ₽</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $purchase->status === 'posted' ? 'bg-green-100 text-green-800' : ($purchase->status === 'cancelled' ? 'bg-slate-100 text-slate-800' : 'bg-yellow-100 text-yellow-800') }}">
                                {{ $purchase->status_name }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <a href="{{ route('purchases.show', $purchase) }}" class="text-blue-600 hover:text-blue-800">Открыть</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-slate-500">Закупки не найдены</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t">
            {{ $purchases->links() }}
        </div>
    </div>
</div>
@endsection
