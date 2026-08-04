@extends('layouts.app')

@section('title', 'Списание ' . $writeOff->number . ' - ICT Help')

@section('content')
<div class="container-width section-padding">
    <div class="mb-5">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li><a href="{{ route('home') }}" class="text-sm text-slate-500 hover:text-slate-700">Главная</a></li>
                <li><span class="mx-2 text-slate-400">/</span><a href="{{ route('consumable-write-offs.index') }}" class="text-sm text-slate-500 hover:text-slate-700">Списания расходников</a></li>
                <li><span class="mx-2 text-slate-400">/</span><span class="text-sm text-slate-500">{{ $writeOff->number }}</span></li>
            </ol>
        </nav>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">{{ session('success') }}</div>
    @endif

    <h1 class="text-3xl font-bold text-slate-900 mb-8">Списание {{ $writeOff->number }}</h1>

    <div class="card p-6 mb-8">
        <dl class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
                <dt class="text-sm font-medium text-slate-500 mb-1">Дата</dt>
                <dd class="text-base text-slate-900">{{ $writeOff->written_off_at->format('d.m.Y') }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500 mb-1">Причина</dt>
                <dd class="text-base text-slate-900">{{ $writeOff->reason ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500 mb-1">Всего единиц</dt>
                <dd class="text-base text-slate-900 font-semibold">{{ $writeOff->total_quantity }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500 mb-1">Списал</dt>
                <dd class="text-base text-slate-900">{{ $writeOff->writtenOffByUser->name ?? '—' }}</dd>
            </div>
            @if($writeOff->comment)
            <div class="col-span-full">
                <dt class="text-sm font-medium text-slate-500 mb-1">Комментарий</dt>
                <dd class="text-base text-slate-900 whitespace-pre-line">{{ $writeOff->comment }}</dd>
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
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Расходник</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Списано</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Текущий остаток</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($writeOff->items as $item)
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if($item->consumable)
                            <a href="{{ route('consumables.show', $item->consumable) }}" class="text-blue-600 hover:text-blue-800">{{ $item->consumable->name }}</a>
                            @else
                            <span class="text-gray-500">Расходник удалён</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $item->quantity }} {{ $item->consumable->unit ?? '' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $item->consumable->quantity ?? '—' }} {{ $item->consumable->unit ?? '' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @include('documents.partials.attach-list', [
        'documentable' => $writeOff,
        'documentTypeSlug' => 'consumable-write-off',
        'documents' => $writeOff->documents,
    ])

    <a href="{{ route('consumable-write-offs.index') }}" class="btn-outline">Вернуться к списку</a>
</div>
@endsection
