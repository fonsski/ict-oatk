@extends('layouts.app')

@section('title', 'Акт ' . $writeOff->number . ' - ICT Help')

@section('content')
<div class="container-width section-padding">
    <div class="mb-5">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li><a href="{{ route('home') }}" class="text-sm text-slate-500 hover:text-slate-700">Главная</a></li>
                <li><span class="mx-2 text-slate-400">/</span><a href="{{ route('write-offs.index') }}" class="text-sm text-slate-500 hover:text-slate-700">Акты списания</a></li>
                <li><span class="mx-2 text-slate-400">/</span><span class="text-sm text-slate-500">{{ $writeOff->number }}</span></li>
            </ol>
        </nav>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded relative mb-4">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative mb-4">
        <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
    @endif

    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 mb-2">Акт списания {{ $writeOff->number }}</h1>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                {{ $writeOff->isPosted() ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                {{ $writeOff->status_name }}
            </span>
        </div>
        @if(auth()->user()->hasRole(['admin', 'master']) && $writeOff->isDraft())
        <div class="flex gap-2">
            <form action="{{ route('write-offs.post', $writeOff) }}" method="POST"
                  onsubmit="return confirm('Провести акт? {{ $writeOff->items->count() }} ед. оборудования получат статус «Списано». Действие необратимо.')">
                @csrf
                <button type="submit" class="btn-primary">Провести акт</button>
            </form>
            <form action="{{ route('write-offs.destroy', $writeOff) }}" method="POST"
                  onsubmit="return confirm('Удалить акт «{{ $writeOff->number }}»?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-danger">Удалить</button>
            </form>
        </div>
        @endif
    </div>

    @if($writeOff->isDraft())
    <div class="bg-yellow-50 border border-yellow-300 text-yellow-800 px-4 py-3 rounded mb-8">
        Акт ещё не проведён — статусы оборудования не изменены. Приложите документ (акт списания) и нажмите «Провести акт».
    </div>
    @endif

    <div class="card p-6 mb-8">
        <dl class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
                <dt class="text-sm font-medium text-slate-500 mb-1">Дата</dt>
                <dd class="text-base text-slate-900">{{ $writeOff->date->format('d.m.Y') }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500 mb-1">Причина</dt>
                <dd class="text-base text-slate-900">{{ $writeOff->reason }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500 mb-1">Основание</dt>
                <dd class="text-base text-slate-900">{{ $writeOff->basis ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500 mb-1">Составил</dt>
                <dd class="text-base text-slate-900">{{ $writeOff->createdBy->name ?? '—' }}</dd>
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
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Позиции акта ({{ $writeOff->items->count() }})</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 uppercase">Инв. номер</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 uppercase">Название</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 uppercase">Кабинет</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 uppercase">Статус</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @foreach($writeOff->items as $item)
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if($item->equipment)
                            <a href="{{ route('equipment.show', $item->equipment) }}" class="text-blue-600 hover:text-blue-800 font-medium">
                                {{ $item->equipment->inventory_number }}
                            </a>
                            @else
                            <span class="text-slate-500">Оборудование удалено</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $item->equipment->name ?? '—' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $item->equipment->room->display_label ?? '—' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $item->equipment->status->name ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @include('documents.partials.attach-list', [
        'documentable' => $writeOff,
        'documentTypeSlug' => 'write-off',
        'documents' => $writeOff->documents,
    ])

    <a href="{{ route('write-offs.index') }}" class="btn-outline">Вернуться к списку</a>
</div>
@endsection
