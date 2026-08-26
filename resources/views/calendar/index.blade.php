@extends('layouts.app')

@section('title', 'Календарь - ICT Help')

@php
    $ruMonths = [1=>'Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'];
    $weekdayNames = ['Пн','Вт','Ср','Чт','Пт','Сб','Вс'];
    // Палитра меток событий — ключи из CalendarEvent::COLORS.
    $palette = [
        'blue'   => 'bg-blue-100 text-blue-800 hover:bg-blue-200',
        'green'  => 'bg-green-100 text-green-800 hover:bg-green-200',
        'red'    => 'bg-red-100 text-red-800 hover:bg-red-200',
        'amber'  => 'bg-amber-100 text-amber-800 hover:bg-amber-200',
        'purple' => 'bg-purple-100 text-purple-800 hover:bg-purple-200',
        'slate'  => 'bg-slate-200 text-slate-800 hover:bg-slate-300',
    ];
@endphp

@section('content')
<div class="container-width section-padding">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
        <div class="flex items-center gap-3">
            <h1 class="text-3xl font-bold text-slate-900">
                {{ $ruMonths[$month->month] }} {{ $month->year }}
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('calendar.index', ['month' => $prevMonth]) }}"
               class="p-2 rounded-md text-slate-500 hover:text-slate-700 hover:bg-slate-100" title="Предыдущий месяц" aria-label="Предыдущий месяц">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <a href="{{ route('calendar.index') }}" class="btn-outline py-1.5 px-4 text-sm">Сегодня</a>
            <a href="{{ route('calendar.index', ['month' => $nextMonth]) }}"
               class="p-2 rounded-md text-slate-500 hover:text-slate-700 hover:bg-slate-100" title="Следующий месяц" aria-label="Следующий месяц">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
            <button type="button" onclick="openEventModal()" class="btn-primary py-1.5 px-4 text-sm ml-2">
                Создать
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <!-- Сетка месяца -->
    <div class="card overflow-hidden">
        <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50">
            @foreach ($weekdayNames as $wd)
                <div class="px-2 py-2 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide">{{ $wd }}</div>
            @endforeach
        </div>

        <div class="grid grid-cols-7">
            @foreach ($weeks as $week)
                @foreach ($week as $cell)
                    @php $date = $cell['date']; @endphp
                    <div class="min-h-[110px] border-b border-r border-slate-100 p-1.5 flex flex-col gap-1
                                {{ $cell['inMonth'] ? 'bg-white' : 'bg-slate-50/60' }}">
                        <button type="button"
                                onclick="openEventModal('{{ $date->toDateString() }}')"
                                class="self-start text-xs w-7 h-7 flex items-center justify-center rounded-full transition
                                       {{ $date->toDateString() === $today->toDateString()
                                            ? 'bg-blue-600 text-white font-semibold'
                                            : ($cell['inMonth'] ? 'text-slate-700 hover:bg-slate-100' : 'text-slate-400 hover:bg-slate-100') }}"
                                title="Добавить событие">
                            {{ $date->day }}
                        </button>

                        <div class="flex flex-col gap-0.5">
                            @foreach ($cell['occurrences']->take(3) as $occ)
                                <a href="{{ route('calendar.show', $occ->event) }}"
                                   class="block truncate rounded px-1.5 py-0.5 text-xs font-medium transition {{ $palette[$occ->color()] ?? $palette['blue'] }}"
                                   title="{{ $occ->title }}">
                                    @unless ($occ->isAllDay())
                                        <span class="tabular-nums opacity-70">{{ $occ->startsAt->format('H:i') }}</span>
                                    @endunless
                                    {{ $occ->title }}
                                </a>
                            @endforeach

                            @if ($cell['occurrences']->count() > 3)
                                <span class="px-1.5 text-xs text-slate-500">Ещё {{ $cell['occurrences']->count() - 3 }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            @endforeach
        </div>
    </div>
</div>

@include('calendar.partials.event-modal', ['rooms' => $rooms, 'staff' => $staff])
@endsection
