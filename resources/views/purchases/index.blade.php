@extends('layouts.app')

@section('title', 'Закупки - ICT Help')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Закупки</h1>

        @if(auth()->user()->hasRole(['admin', 'master']))
        <a href="{{ route('purchases.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            Новая закупка
        </a>
        @endif
    </div>

    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
        {{ session('success') }}
    </div>
    @endif

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="p-4 border-b">
            <form method="GET" class="flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Поставщик</label>
                    <input type="text" name="supplier" value="{{ request('supplier') }}" class="rounded border-gray-300 px-3 py-2" />
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Статус</label>
                    <select name="status" class="rounded border-gray-300 px-3 py-2">
                        <option value="">Все статусы</option>
                        @foreach(\App\Models\Purchase::STATUSES as $value => $label)
                        <option value="{{ $value }}" {{ request('status') == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">С даты</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="rounded border-gray-300 px-3 py-2" />
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">По дату</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="rounded border-gray-300 px-3 py-2" />
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Сумма от</label>
                    <input type="number" step="0.01" name="sum_from" value="{{ request('sum_from') }}" class="rounded border-gray-300 px-3 py-2 w-28" />
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Сумма до</label>
                    <input type="number" step="0.01" name="sum_to" value="{{ request('sum_to') }}" class="rounded border-gray-300 px-3 py-2 w-28" />
                </div>
                <button type="submit" class="bg-gray-800 text-white px-3 py-2 rounded">Применить</button>
                <a href="{{ route('purchases.index') }}" class="text-sm text-gray-500 underline">Сбросить</a>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Номер</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дата</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Поставщик</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Сумма</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Действия</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($purchases as $purchase)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $purchase->number }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $purchase->date->format('d.m.Y') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $purchase->supplier }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ number_format($purchase->total_sum, 2, ',', ' ') }} ₽</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $purchase->status === 'posted' ? 'bg-green-100 text-green-800' : ($purchase->status === 'cancelled' ? 'bg-gray-100 text-gray-800' : 'bg-yellow-100 text-yellow-800') }}">
                                {{ $purchase->status_name }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <a href="{{ route('purchases.show', $purchase) }}" class="text-blue-600 hover:text-blue-800">Открыть</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">Закупки не найдены</td>
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
