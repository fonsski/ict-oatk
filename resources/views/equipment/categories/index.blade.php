@extends('layouts.app')

@section('title', 'Категории оборудования')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Категории оборудования</h1>

        <a href="{{ route('equipment.equipment-categories.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            Добавить категорию
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
        {{ session('error') }}
    </div>
    @endif

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="p-4 border-b">
            <form method="GET" class="flex flex-wrap gap-3 items-center">
                <div>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Поиск по названию" class="rounded border-gray-300 px-3 py-2" />
                </div>
                <div>
                    <select name="sort" class="rounded border-gray-300 px-3 py-2">
                        <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Сортировка по названию</option>
                        <option value="created_at" {{ request('sort') == 'created_at' ? 'selected' : '' }}>Сортировка по дате создания</option>
                    </select>
                </div>
                <div>
                    <select name="direction" class="rounded border-gray-300 px-3 py-2">
                        <option value="asc" {{ request('direction') == 'asc' ? 'selected' : '' }}>По возрастанию</option>
                        <option value="desc" {{ request('direction') == 'desc' ? 'selected' : '' }}>По убыванию</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="bg-gray-800 text-white px-3 py-2 rounded">Применить</button>
                </div>
                <div>
                    <a href="{{ route('equipment.equipment-categories.index') }}" class="text-sm text-gray-500 underline">Сбросить</a>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Название
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Описание
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Кол-во оборудования
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Действия
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($categories as $category)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $category->name }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-500">{{ $category->description ?? 'Нет описания' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                {{ $category->equipment_count }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="{{ route('equipment.equipment-categories.show', $category) }}" class="text-blue-600 hover:text-blue-900 mr-3">
                                Просмотр
                            </a>
                            <a href="{{ route('equipment.equipment-categories.edit', $category) }}" class="text-blue-600 hover:text-blue-900 mr-3">
                                Изменить
                            </a>
                            <form action="{{ route('equipment.equipment-categories.destroy', $category) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Вы уверены? Это действие удалит категорию.')">
                                    Удалить
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 text-gray-400 mb-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="8" x2="12" y2="12"></line>
                                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                </svg>
                                <h3 class="text-lg font-medium text-gray-900 mb-1">Категорий не найдено</h3>
                                <p class="text-gray-500">Попробуйте изменить параметры поиска или создайте новую категорию</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($categories->hasPages())
        <div class="px-6 py-4">
            {{ $categories->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
