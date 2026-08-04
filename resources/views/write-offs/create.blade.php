@extends('layouts.app')

@section('title', 'Новый акт списания - ICT Help')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white shadow-md rounded-lg p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Новый акт списания оборудования</h1>
            <p class="text-sm text-gray-600 mb-6">Выбрано единиц: <span class="font-semibold">{{ $equipment->count() }}</span>. После создания акта приложите документ и проведите его — только тогда оборудование получит статус «Списано».</p>

            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('write-offs.store') }}" method="POST">
                @csrf

                @foreach($equipment as $item)
                <input type="hidden" name="equipment_ids[]" value="{{ $item->id }}">
                @endforeach

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="date" class="block text-gray-700 text-sm font-bold mb-2">Дата акта *</label>
                        <input type="date" name="date" id="date" required max="{{ now()->format('Y-m-d') }}"
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('date') border-red-500 @enderror"
                               value="{{ old('date', now()->format('Y-m-d')) }}">
                        @error('date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="reason" class="block text-gray-700 text-sm font-bold mb-2">Причина списания *</label>
                        <input type="text" name="reason" id="reason" required maxlength="255"
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('reason') border-red-500 @enderror"
                               value="{{ old('reason') }}" placeholder="Например: физический износ, неремонтопригодность">
                        @error('reason')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="mb-4">
                    <label for="basis" class="block text-gray-700 text-sm font-bold mb-2">Основание</label>
                    <input type="text" name="basis" id="basis" maxlength="255"
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                           value="{{ old('basis') }}" placeholder="Например: приказ № 12 от 01.08.2026">
                    @error('basis')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="mb-6">
                    <label for="comment" class="block text-gray-700 text-sm font-bold mb-2">Комментарий</label>
                    <textarea name="comment" id="comment" rows="2" maxlength="1000"
                              class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">{{ old('comment') }}</textarea>
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Позиции акта</label>
                    <div class="overflow-x-auto border border-gray-200 rounded">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Инв. номер</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Название</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Категория</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Кабинет</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Текущий статус</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($equipment as $item)
                                <tr>
                                    <td class="px-3 py-2 whitespace-nowrap font-medium">{{ $item->inventory_number }}</td>
                                    <td class="px-3 py-2">{{ $item->name ?: '—' }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap">{{ $item->category->name ?? '—' }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap">{{ $item->room->display_label ?? '—' }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap">{{ $item->status->name ?? '—' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Создать акт
                    </button>
                    <a href="{{ route('equipment.index') }}" class="text-gray-600 hover:text-gray-800 font-medium">Отмена</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
