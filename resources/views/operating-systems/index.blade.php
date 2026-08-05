@extends('layouts.app')

@section('title', 'Операционные системы - ICT Help')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Операционные системы</h1>
            <p class="text-gray-600 mt-1">Справочник ОС для компьютеров и ноутбуков</p>
        </div>

        @if(auth()->user()->hasRole(['admin', 'master']))
        <a href="{{ route('operating-systems.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            Добавить ОС
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
                    <label class="block text-xs text-gray-500 mb-1">Поиск</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Название или семейство" class="rounded border-gray-300 px-3 py-2 w-64" />
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Семейство</label>
                    <select name="family" class="rounded border-gray-300 px-3 py-2">
                        <option value="">Все семейства</option>
                        @foreach($families as $family)
                        <option value="{{ $family }}" {{ request('family') === $family ? 'selected' : '' }}>{{ $family }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="bg-gray-800 text-white px-3 py-2 rounded">Применить</button>
                <a href="{{ route('operating-systems.index') }}" class="text-sm text-gray-500 underline">Сбросить</a>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Название</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Семейство</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Оборудования</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Действия</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($operatingSystems as $operatingSystem)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $operatingSystem->name }}</div>
                            @if($operatingSystem->description)
                            <div class="text-xs text-gray-500">{{ $operatingSystem->description }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $operatingSystem->family ?: '—' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @if($operatingSystem->equipment_count > 0)
                            <a href="{{ route('equipment.index', ['operating_system_id' => $operatingSystem->id]) }}" class="text-blue-600 hover:text-blue-800">
                                {{ $operatingSystem->equipment_count }} ед.
                            </a>
                            @else
                            <span class="text-gray-500">0</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @if($operatingSystem->is_active)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Активна</span>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Скрыта</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @if(auth()->user()->hasRole(['admin', 'master']))
                            <a href="{{ route('operating-systems.edit', $operatingSystem) }}" class="text-blue-600 hover:text-blue-800">Изменить</a>
                            <form action="{{ route('operating-systems.destroy', $operatingSystem) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Удалить «{{ $operatingSystem->name }}»?@if($operatingSystem->equipment_count > 0) У {{ $operatingSystem->equipment_count }} ед. оборудования ОС станет не указана.@endif')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 ml-3">Удалить</button>
                            </form>
                            @else
                            <span class="text-gray-400">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">Операционные системы не найдены</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t">
            {{ $operatingSystems->links() }}
        </div>
    </div>
</div>
@endsection
