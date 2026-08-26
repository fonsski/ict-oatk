@extends('layouts.app')

@section('title', $event->title . ' - Календарь')

@php
    $ruMonths = [1=>'января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря'];
    $fmt = function ($dt) use ($ruMonths) {
        return $dt->day . ' ' . $ruMonths[$dt->month] . ' ' . $dt->year;
    };
    $canManage = auth()->user()->hasRole(['admin', 'master']) || $event->organizer_id === auth()->id();
    $myParticipation = $event->participants->firstWhere('user_id', auth()->id());
@endphp

@section('content')
<div class="container-width section-padding">
    <div class="max-w-2xl mx-auto">
        <a href="{{ route('calendar.index', ['month' => $event->starts_at->format('Y-m')]) }}"
           class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700 mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            К календарю
        </a>

        @if (session('success'))
            <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif

        <div class="card p-6">
            <div class="flex items-start justify-between gap-4">
                <h1 class="text-2xl font-bold text-slate-900">{{ $event->title }}</h1>
                @if ($event->isCancelled())
                    <span class="shrink-0 inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">Отменено</span>
                @endif
            </div>

            <dl class="mt-5 space-y-3 text-sm">
                <div class="flex gap-3">
                    <dt class="w-32 shrink-0 text-slate-500">Когда</dt>
                    <dd class="text-slate-900">
                        @if ($event->all_day)
                            {{ $fmt($event->starts_at) }}
                            @if ($event->starts_at->toDateString() !== $event->ends_at->toDateString())
                                — {{ $fmt($event->ends_at) }}
                            @endif
                            <span class="text-slate-500">(весь день)</span>
                        @else
                            {{ $fmt($event->starts_at) }}, {{ $event->starts_at->format('H:i') }}–{{ $event->ends_at->format('H:i') }}
                        @endif
                    </dd>
                </div>

                @if ($event->isRecurring())
                    <div class="flex gap-3">
                        <dt class="w-32 shrink-0 text-slate-500">Повторение</dt>
                        <dd class="text-slate-900">
                            {{ $event->recurrenceSummary() }}
                            @if ($occurrenceDate)
                                <span class="block text-xs text-slate-500 mt-0.5">Открыта дата: {{ $occurrenceDate->format('d.m.Y') }}</span>
                            @endif
                        </dd>
                    </div>
                @endif

                @if ($event->room || $event->location)
                    <div class="flex gap-3">
                        <dt class="w-32 shrink-0 text-slate-500">Место</dt>
                        <dd class="text-slate-900">
                            @if ($event->room)
                                Кабинет {{ $event->room->number }}{{ $event->room->name ? ' — ' . $event->room->name : '' }}
                            @endif
                            @if ($event->room && $event->location) · @endif
                            {{ $event->location }}
                        </dd>
                    </div>
                @endif

                <div class="flex gap-3">
                    <dt class="w-32 shrink-0 text-slate-500">Организатор</dt>
                    <dd class="text-slate-900">{{ $event->organizer?->name ?? '—' }}</dd>
                </div>

                @if ($event->participants->isNotEmpty())
                    <div class="flex gap-3">
                        <dt class="w-32 shrink-0 text-slate-500">Участники</dt>
                        <dd class="text-slate-900 space-y-1">
                            @foreach ($event->participants as $p)
                                <div class="flex items-center gap-2">
                                    <span>{{ $p->user?->name ?? '—' }}</span>
                                    <span class="text-xs text-slate-500">· {{ $p->response_label }}</span>
                                </div>
                            @endforeach
                        </dd>
                    </div>
                @endif

                @if ($event->description)
                    <div class="flex gap-3">
                        <dt class="w-32 shrink-0 text-slate-500">Описание</dt>
                        <dd class="text-slate-900 whitespace-pre-line">{{ $event->description }}</dd>
                    </div>
                @endif
            </dl>

            @if ($myParticipation)
                <div class="mt-6 pt-4 border-t border-slate-100">
                    <p class="text-sm text-slate-500 mb-2">
                        Вас пригласили на это событие.
                        @if ($myParticipation->response !== 'pending')
                            Ваш ответ: <span class="font-medium text-slate-700">{{ $myParticipation->response_label }}</span>.
                        @endif
                    </p>
                    <form method="POST" action="{{ route('calendar.respond', $event) }}" class="flex flex-wrap items-center gap-2">
                        @csrf
                        @foreach (['accepted' => 'Приду', 'maybe' => 'Возможно', 'declined' => 'Не приду'] as $value => $label)
                            <button type="submit" name="response" value="{{ $value }}"
                                    class="py-1.5 px-4 text-sm rounded-md border transition
                                        {{ $myParticipation->response === $value
                                            ? 'bg-blue-600 border-blue-600 text-white'
                                            : 'border-slate-300 text-slate-700 hover:bg-slate-50' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </form>
                </div>
            @endif

            @if ($canManage)
                <div class="mt-6 flex flex-wrap items-center gap-3 pt-4 border-t border-slate-100">
                    <a href="{{ route('calendar.edit', $event) }}" class="btn-outline py-1.5 px-4 text-sm">Редактировать</a>

                    @if ($event->isRecurring() && $occurrenceDate)
                        {{-- Отменить только эту дату серии --}}
                        <form method="POST" action="{{ route('calendar.cancel-occurrence', $event) }}"
                              onsubmit="return confirm('Отменить событие только {{ $occurrenceDate->format('d.m.Y') }}?')">
                            @csrf
                            <input type="hidden" name="date" value="{{ $occurrenceDate->toDateString() }}">
                            <button type="submit" class="py-1.5 px-4 text-sm text-amber-700 hover:text-amber-800 hover:bg-amber-50 rounded-md">
                                Отменить {{ $occurrenceDate->format('d.m') }}
                            </button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('calendar.destroy', $event) }}"
                          onsubmit="return confirm('{{ $event->isRecurring() ? 'Удалить всю серию повторов?' : 'Удалить это событие?' }}')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="py-1.5 px-4 text-sm text-red-600 hover:text-red-700 hover:bg-red-50 rounded-md">
                            {{ $event->isRecurring() ? 'Удалить всю серию' : 'Удалить' }}
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
