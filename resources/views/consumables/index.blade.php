@extends('layouts.app')

@section('title', 'Расходники')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Расходники</h1>

        @if(auth()->user()->hasRole(['admin', 'master']))
        <a href="{{ route('consumables.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            Добавить расходник
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
            <form method="GET" class="flex flex-wrap gap-3 items-center">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Поиск по названию или категории" class="rounded border-gray-300 px-3 py-2 w-64" />
                <button type="submit" class="bg-gray-800 text-white px-3 py-2 rounded">Применить</button>
                <a href="{{ route('consumables.index') }}" class="text-sm text-gray-500 underline">Сбросить</a>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Название / категория</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Всего</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Использование</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ответственный</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Действия</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($consumables as $consumable)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $consumable->name }}</div>
                            @if($consumable->category)
                            <div class="text-xs text-gray-500">{{ $consumable->category }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            {{ $consumable->quantity_total }} {{ $consumable->unit }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800" title="В наличии">
                                {{ $consumable->quantity_in_stock }} в наличии
                            </span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800" title="Установлено">
                                {{ $consumable->quantity_installed }} установлено
                            </span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800" title="Списано">
                                {{ $consumable->quantity_written_off }} списано
                            </span>
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
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">Расходники не найдены</td>
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
