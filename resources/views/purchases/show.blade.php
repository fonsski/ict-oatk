@extends('layouts.app')

@section('title', 'Закупка ' . $purchase->number . ' - ICT Help')

@section('content')
<div class="container-width section-padding">
    <div class="mb-5">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li><a href="{{ route('home') }}" class="text-sm text-slate-500 hover:text-slate-700">Главная</a></li>
                <li><span class="mx-2 text-slate-400">/</span><a href="{{ route('purchases.index') }}" class="text-sm text-slate-500 hover:text-slate-700">Закупки</a></li>
                <li><span class="mx-2 text-slate-400">/</span><span class="text-sm text-slate-500">{{ $purchase->number }}</span></li>
            </ol>
        </nav>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
        <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
    @endif

    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 mb-2">Закупка {{ $purchase->number }}</h1>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                {{ $purchase->status === 'posted' ? 'bg-green-100 text-green-800' : ($purchase->status === 'cancelled' ? 'bg-gray-100 text-gray-800' : 'bg-yellow-100 text-yellow-800') }}">
                {{ $purchase->status_name }}
            </span>
        </div>
        @if(auth()->user()->hasRole(['admin', 'master']))
        <div class="flex gap-2">
            @if($purchase->isDraft())
                <a href="{{ route('purchases.edit', $purchase) }}" class="btn-secondary">Изменить</a>
                <a href="{{ route('purchases.post.form', $purchase) }}" class="btn-primary">Провести</a>
                <form action="{{ route('purchases.destroy', $purchase) }}" method="POST" onsubmit="return confirm('Удалить закупку «{{ $purchase->number }}»?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-danger">Удалить</button>
                </form>
            @endif
            <a href="{{ route('purchases.request-document', $purchase) }}" class="btn-secondary">Заявка ТМЦ (.docx)</a>
        </div>
        @endif
    </div>

    <div class="card p-6 mb-8">
        <dl class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
                <dt class="text-sm font-medium text-slate-500 mb-1">Дата</dt>
                <dd class="text-base text-slate-900">{{ $purchase->date->format('d.m.Y') }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500 mb-1">Поставщик</dt>
                <dd class="text-base text-slate-900">{{ $purchase->supplier }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500 mb-1">Итоговая сумма</dt>
                <dd class="text-base text-slate-900 font-semibold">{{ number_format($purchase->total_sum, 2, ',', ' ') }} ₽</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500 mb-1">Оформил</dt>
                <dd class="text-base text-slate-900">{{ $purchase->createdBy->name ?? '—' }}</dd>
            </div>
            @if($purchase->comment)
            <div class="col-span-full">
                <dt class="text-sm font-medium text-slate-500 mb-1">Комментарий</dt>
                <dd class="text-base text-slate-900 whitespace-pre-line">{{ $purchase->comment }}</dd>
            </div>
            @endif
        </dl>
    </div>

    <div class="card p-6 mb-8">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Позиции</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Тип</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Наименование</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Кол-во</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Цена</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Сумма</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($purchase->items as $item)
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $item->type_name }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            {{ $item->name }}
                            @if($item->consumable)
                                <div class="text-xs text-gray-400">Расходник: <a href="{{ route('consumables.show', $item->consumable) }}" class="text-blue-600 hover:text-blue-800">{{ $item->consumable->name }}</a></div>
                            @elseif($item->equipmentCategory)
                                <div class="text-xs text-gray-400">Категория: {{ $item->equipmentCategory->name }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $item->quantity }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ number_format($item->unit_price, 2, ',', ' ') }} ₽</td>
                        <td class="px-4 py-3 whitespace-nowrap font-medium">{{ number_format($item->sum, 2, ',', ' ') }} ₽</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @include('documents.partials.attach-list', [
        'documentable' => $purchase,
        'documentTypeSlug' => 'purchase',
        'documents' => $purchase->documents,
    ])

    <a href="{{ route('purchases.index') }}" class="btn-outline">Вернуться к списку</a>
</div>
@endsection
