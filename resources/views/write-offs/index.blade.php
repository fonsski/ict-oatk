@extends('layouts.app')

@section('title', 'Акты списания оборудования - ICT Help')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Акты списания оборудования</h1>
        <a href="{{ route('equipment.index') }}" class="btn-secondary">К оборудованию</a>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
        <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
    @endif

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="p-4 border-b">
            <form method="GET" class="flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Статус</label>
                    <select name="status" class="rounded border-gray-300 px-3 py-2">
                        <option value="">Все статусы</option>
                        @foreach(\App\Models\WriteOff::STATUSES as $value => $label)
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
                <button type="submit" class="bg-gray-800 text-white px-3 py-2 rounded">Применить</button>
                <a href="{{ route('write-offs.index') }}" class="text-sm text-gray-500 underline">Сбросить</a>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Номер</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дата</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Единиц</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Причина</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Действия</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($writeOffs as $writeOff)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $writeOff->number }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $writeOff->date->format('d.m.Y') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $writeOff->items_count }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $writeOff->reason }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $writeOff->isPosted() ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ $writeOff->status_name }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <a href="{{ route('write-offs.show', $writeOff) }}" class="text-blue-600 hover:text-blue-800">Открыть</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            Актов пока нет. Выберите оборудование в списке инвентаря и нажмите «Списать выбранные».
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t">
            {{ $writeOffs->links() }}
        </div>
    </div>
</div>
@endsection
