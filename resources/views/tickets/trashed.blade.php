@extends('layouts.app')

@section('title', 'Корзина заявок - ICT')

@section('content')
<div class="container-width section-padding">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 mb-2">Корзина заявок</h1>
            <p class="text-slate-600">Удалённые заявки можно восстановить или удалить безвозвратно</p>
        </div>
        <a href="{{ route('tickets.index') }}" class="btn-secondary mt-4 sm:mt-0">
            К списку заявок
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6">
        <p class="text-sm text-green-700">{{ session('success') }}</p>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
        <p class="text-sm text-red-700">{{ session('error') }}</p>
    </div>
    @endif

    <!-- Filters -->
    <div class="card p-6 mb-8">
        <form method="GET" action="{{ route('tickets.trashed') }}" class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <label for="search" class="form-label">Поиск</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}"
                       class="form-input" placeholder="Тема или имя заявителя">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn-primary">Найти</button>
                @if(request('search'))
                <a href="{{ route('tickets.trashed') }}" class="btn-secondary">Сбросить</a>
                @endif
            </div>
        </form>
    </div>

    <!-- List -->
    <div class="card overflow-hidden">
        @if($tickets->count())
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Заявка</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Заявитель</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Статус</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Удалена</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">Действия</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @foreach($tickets as $ticket)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-slate-900">{{ $ticket->title }}</div>
                            <div class="text-sm text-slate-500">#{{ $ticket->id }} · {{ format_ticket_category($ticket->category) }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">
                            {{ $ticket->reporter_name ?? optional($ticket->user)->name ?? '—' }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ get_status_badge_class($ticket->status) }} whitespace-nowrap">
                                {{ format_ticket_status($ticket->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600 whitespace-nowrap">
                            {{ format_datetime($ticket->deleted_at) }}
                        </td>
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            <form method="POST" action="{{ route('tickets.restore', $ticket->id) }}" class="inline">
                                @csrf
                                <button type="submit" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                    Восстановить
                                </button>
                            </form>

                            @if(user_is_admin())
                            <form method="POST" action="{{ route('tickets.force-delete', $ticket->id) }}" class="inline ml-4"
                                  onsubmit="return confirm('Заявка #{{ $ticket->id }} будет удалена безвозвратно вместе с комментариями. Продолжить?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">
                                    Удалить навсегда
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-slate-200">
            {{ $tickets->links() }}
        </div>
        @else
        <div class="text-center py-16">
            <h3 class="text-lg font-medium text-slate-900 mb-2">Корзина пуста</h3>
            <p class="text-slate-600">Удалённые заявки будут появляться здесь</p>
        </div>
        @endif
    </div>
</div>
@endsection
