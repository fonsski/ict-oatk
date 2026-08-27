@extends('layouts.app')

@section('title', 'Заявка ТМЦ ' . ($request->number ?: '#'.$request->id) . ' - ICT Help')

@section('content')
<div class="max-w-5xl mx-auto px-6 py-8">
    <div class="mb-5">
        <nav class="text-sm text-slate-500">
            <a href="{{ route('tmc-requests.index') }}" class="hover:text-slate-700">Заявки ТМЦ</a>
            <span class="mx-1">/</span><span>{{ $request->number ?: '#'.$request->id }}</span>
        </nav>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">{{ session('success') }}</div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <h1 class="text-3xl font-bold text-slate-900">Заявка ТМЦ {{ $request->number ?: '#'.$request->id }}</h1>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('tmc-requests.document', $request) }}" class="btn-primary">Скачать .docx</a>
            <a href="{{ route('tmc-requests.edit', $request) }}" class="btn-secondary">Изменить</a>
            <form action="{{ route('tmc-requests.destroy', $request) }}" method="POST" onsubmit="return confirm('Удалить заявку?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-danger">Удалить</button>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 mb-8">
        <dl class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <dt class="text-sm font-medium text-slate-500 mb-1">Дата</dt>
                <dd class="text-base text-slate-900">{{ $request->date->format('d.m.Y') }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500 mb-1">Итого</dt>
                <dd class="text-base text-slate-900 font-semibold">{{ number_format($request->total_sum, 2, ',', ' ') }} ₽</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500 mb-1">Составил</dt>
                <dd class="text-base text-slate-900">{{ $request->createdBy->name ?? '—' }}</dd>
            </div>
        </dl>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 mb-8">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Позиции</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 uppercase">№</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 uppercase">Наименование</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 uppercase">Кол-во</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 uppercase">Ед. измер.</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 uppercase">Цена</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 uppercase">Стоимость</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($request->items as $i => $item)
                    <tr>
                        <td class="px-4 py-3 text-slate-700">{{ $i + 1 }}</td>
                        <td class="px-4 py-3 text-slate-900">{{ $item->name }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $item->quantity }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $item->unit ?: 'шт.' }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ number_format($item->unit_price, 2, ',', ' ') }} ₽</td>
                        <td class="px-4 py-3 text-slate-900 font-medium">{{ number_format($item->sum, 2, ',', ' ') }} ₽</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-slate-50 font-semibold">
                        <td class="px-4 py-2 text-right" colspan="5">Итого:</td>
                        <td class="px-4 py-2">{{ number_format($request->total_sum, 2, ',', ' ') }} ₽</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    @if($request->purpose)
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 mb-8">
        <h2 class="text-lg font-semibold text-slate-900 mb-2">Обоснование приобретения</h2>
        <p class="text-slate-700 whitespace-pre-line">{{ $request->purpose }}</p>
        <p class="text-xs text-slate-400 mt-2">В печатной заявке выводится на обороте (вторая страница).</p>
    </div>
    @endif

    @if($request->documents->isNotEmpty())
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Сохранённые файлы</h2>
        <ul class="space-y-2">
            @foreach($request->documents as $doc)
            <li class="flex items-center justify-between">
                <span class="text-sm text-slate-700">{{ $doc->original_name }} <span class="text-xs text-slate-400">· {{ $doc->human_size }} · {{ $doc->created_at->format('d.m.Y H:i') }}</span></span>
                <a href="{{ route('documents.download', $doc) }}" class="text-sm text-blue-600 hover:text-blue-800">Скачать</a>
            </li>
            @endforeach
        </ul>
    </div>
    @endif
</div>
@endsection
