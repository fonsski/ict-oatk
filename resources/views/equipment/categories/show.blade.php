@extends('layouts.app')

@section('title', 'Просмотр категории: ' . $category->name)

@section('content')
<div class="max-w-7xl mx-auto px-6 py-8">
    <div class="mb-6">
        <a href="{{ route('equipment.equipment-categories.index') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Назад к списку категорий
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 mb-6">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 mb-2">{{ $category->name }}</h1>
                <p class="text-slate-600">{{ $category->description ?? 'Описание отсутствует' }}</p>
            </div>
            @if(auth()->user()->canManageEquipment())
            <div class="flex space-x-2">
                <a href="{{ route('equipment.equipment-categories.edit', $category) }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Редактировать
                </a>
                <form action="{{ route('equipment.equipment-categories.destroy', $category) }}" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded"
                            onclick="return confirm('Вы уверены? Это действие удалит категорию.')">
                        Удалить
                    </button>
                </form>
            </div>
            @endif
        </div>
        <div class="mt-4 pt-4 border-t border-slate-200">
            <p class="text-sm text-slate-500">
                Количество единиц оборудования: <span class="font-semibold">{{ $equipment->total() }}</span>
            </p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h2 class="text-xl font-semibold text-slate-800">Оборудование в этой категории</h2>
        </div>

        @if($equipment->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                Инв. номер / Название
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                Статус
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                Кабинет
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                Гарантия
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                Действия
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        @foreach($equipment as $item)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-slate-900">{{ $item->inventory_number }} @if($item->name) — <span class="text-slate-700">{{ $item->name }}</span>@endif</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                        {{ $item->status->slug === 'working' ? 'bg-green-100 text-green-800' :
                                        ($item->status->slug === 'in_service' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                    {{ $item->status->name }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ $item->room ? $item->room->display_label : 'Не указан' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($item->has_warranty)
                                    @if($item->warranty_end_date >= now())
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            До {{ $item->warranty_end_date->format('d.m.Y') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            Истекла {{ $item->warranty_end_date->format('d.m.Y') }}
                                        </span>
                                    @endif
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800">
                                        Нет
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="{{ route('equipment.show', $item) }}" class="text-blue-600 hover:text-blue-900 mr-3">
                                    Просмотр
                                </a>
                                @if(optional(auth()->user())->canManageEquipment())
                                <a href="{{ route('equipment.edit', $item) }}" class="text-blue-600 hover:text-blue-900 mr-3">
                                    Изменить
                                </a>
                                <form action="{{ route('equipment.destroy', $item) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Вы уверены?')">
                                        Удалить
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($equipment->hasPages())
            <div class="px-6 py-4">
                {{ $equipment->links() }}
            </div>
            @endif
        @else
            <div class="px-6 py-12 text-center text-slate-500">
                <div class="flex flex-col items-center">
                    <svg class="w-12 h-12 text-slate-400 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="text-lg font-medium text-slate-900 mb-1">В этой категории нет оборудования</h3>
                    <p class="text-slate-500 mb-4">Оборудование данной категории еще не добавлено в систему</p>

                    @if(optional(auth()->user())->canManageEquipment())
                    <a href="{{ route('equipment.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                        Добавить оборудование
                    </a>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
