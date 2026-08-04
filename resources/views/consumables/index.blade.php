@extends('layouts.app')

@section('title', 'Расходники - ICT Help')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Расходники</h1>

        <div class="flex gap-2">
            <a href="{{ route('consumable-write-offs.index') }}" class="btn-secondary">Акты списания</a>
            @if(auth()->user()->hasRole(['admin', 'master']))
            <a href="{{ route('consumables.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Добавить расходник
            </a>
            @endif
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
        {{ session('success') }}
    </div>
    @endif

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="p-4 border-b">
            <form method="GET" class="flex flex-wrap gap-3 items-center">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Поиск по названию или категории" class="rounded border-gray-300 px-3 py-2 w-64" />
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="low_stock" value="1" {{ request('low_stock') ? 'checked' : '' }} class="rounded border-gray-300">
                    Только с остатком ниже минимального
                </label>
                <button type="submit" class="bg-gray-800 text-white px-3 py-2 rounded">Применить</button>
                <a href="{{ route('consumables.index') }}" class="text-sm text-gray-500 underline">Сбросить</a>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Название / категория</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Остаток</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Мин. остаток</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Место хранения</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ответственный</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Действия</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($consumables as $consumable)
                    <tr class="{{ $consumable->isLowStock() ? 'bg-red-50' : '' }}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $consumable->name }}</div>
                            @if($consumable->category)
                            <div class="text-xs text-gray-500">{{ $consumable->category }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="font-medium {{ $consumable->isLowStock() ? 'text-red-700' : 'text-gray-900' }}">
                                {{ $consumable->quantity }} {{ $consumable->unit }}
                            </span>
                            @if($consumable->isLowStock())
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 ml-1">Ниже минимума</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            {{ $consumable->min_quantity !== null ? $consumable->min_quantity . ' ' . $consumable->unit : '—' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            {{ $consumable->room->display_label ?? '—' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            {{ $consumable->responsibleUser->name ?? 'Не указан' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <a href="{{ route('consumables.show', $consumable) }}" class="text-blue-600 hover:text-blue-800">Открыть</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">Расходники не найдены</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t">
            {{ $consumables->links() }}
        </div>
    </div>
</div>
@endsection
