@extends('layouts.app')

@section('title', 'Заявки ТМЦ - ICT Help')

@section('content')
<div class="max-w-7xl mx-auto px-6 py-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Заявки ТМЦ</h1>
            <p class="text-slate-600 mt-1">Заявки на приобретение товарно-материальных ценностей: заполнить, сохранить, распечатать.</p>
        </div>
        <a href="{{ route('tmc-requests.create') }}" class="inline-flex items-center px-5 py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors self-start">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Новая заявка
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Номер</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Дата</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Позиций</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Сумма</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Составил</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">Действия</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($requests as $req)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <a href="{{ route('tmc-requests.show', $req) }}" class="text-blue-600 hover:text-blue-800 font-medium">{{ $req->number ?: '#'.$req->id }}</a>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700">{{ $req->date->format('d.m.Y') }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700">{{ $req->items_count }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700">{{ number_format($req->total_sum, 2, ',', ' ') }} ₽</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700">{{ $req->createdBy->name ?? '—' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                        <a href="{{ route('tmc-requests.document', $req) }}" class="text-blue-600 hover:text-blue-800">Скачать .docx</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-slate-500">Заявок пока нет</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t border-slate-200">
            {{ $requests->links() }}
        </div>
    </div>
</div>
@endsection
