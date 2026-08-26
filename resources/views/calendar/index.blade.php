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
@php
    $anchor = ($today->year === $month->year && $today->month === $month->month)
        ? $today->toDateString()
        : $month->toDateString();
@endphp
<div class="container-width section-padding">
    @include('calendar.partials.toolbar', [
        'viewMode' => 'month',
        'title' => $ruMonths[$month->month] . ' ' . $month->year,
        'navPrev' => route('calendar.index', ['month' => $prevMonth]),
        'navNext' => route('calendar.index', ['month' => $nextMonth]),
        'navToday' => route('calendar.index'),
        'anchor' => $anchor,
        'anchorMonth' => $month->format('Y-m'),
    ])

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <!-- Сетка месяца -->
    <div class="card overflow-hidden">
        <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50">
            @foreach ($weekdayNames as $idx => $wd)
                <div class="px-2 py-2.5 text-center text-xs font-semibold uppercase tracking-wide
                            {{ $idx >= 5 ? 'text-rose-400' : 'text-slate-500' }}">{{ $wd }}</div>
            @endforeach
        </div>

        <div class="grid grid-cols-7">
            @foreach ($weeks as $week)
                @foreach ($week as $cell)
                    @php
                        $date = $cell['date'];
                        $isToday = $date->toDateString() === $today->toDateString();
                        $isWeekend = $date->dayOfWeekIso >= 6;
                    @endphp
                    <div onclick="if (!event.target.closest('a,button,form')) openEventModal('{{ $date->toDateString() }}')"
                         data-day-cell data-date="{{ $date->toDateString() }}"
                         ondragover="calDragOver(event)" ondragleave="calDragLeave(event)" ondrop="calDrop(event)"
                         class="min-h-[116px] border-b border-r border-slate-100 p-1.5 flex flex-col gap-1 cursor-pointer transition
                                {{ !$cell['inMonth'] ? 'bg-slate-50/50' : ($isToday ? 'bg-blue-50/50' : ($isWeekend ? 'bg-slate-50/40' : 'bg-white')) }}
                                hover:bg-slate-50">
                        <button type="button"
                                onclick="openEventModal('{{ $date->toDateString() }}')"
                                class="self-start text-sm w-7 h-7 flex items-center justify-center rounded-full transition
                                       {{ $isToday
                                            ? 'bg-blue-600 text-white font-semibold'
                                            : ($cell['inMonth'] ? ($isWeekend ? 'text-rose-500 hover:bg-slate-200' : 'text-slate-700 hover:bg-slate-200') : 'text-slate-400 hover:bg-slate-200') }}"
                                title="Добавить событие">
                            {{ $date->day }}
                        </button>

                        <div class="flex flex-col gap-0.5">
                            @foreach ($cell['occurrences']->take(3) as $occ)
                                <a href="{{ route('calendar.show', $occ->event->isRecurring() ? ['event' => $occ->event, 'date' => $occ->occurrenceDate()] : ['event' => $occ->event]) }}"
                                   @unless ($occ->event->isRecurring()) draggable="true" ondragstart="calDragStart(event, {{ $occ->event->id }})" ondragend="calDragEnd(event)" @endunless
                                   @if ($occ->event->declinedByViewer()) data-event-declined @endif
                                   class="block truncate rounded px-1.5 py-0.5 text-xs font-medium transition {{ $palette[$occ->color()] ?? $palette['blue'] }} {{ $occ->event->isRecurring() ? '' : 'cursor-grab active:cursor-grabbing' }} {{ $occ->event->declinedByViewer() ? 'line-through opacity-60' : '' }}"
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

                            {{-- Задачи дня: кружок-переключатель + название --}}
                            @foreach ($cell['tasks']->take(3) as $task)
                                <div class="flex items-center gap-1 group" @if($task->isCompleted()) data-task-done @endif>
                                    <form method="POST" action="{{ route('calendar.tasks.toggle', $task) }}" class="shrink-0">
                                        @csrf
                                        <button type="submit" class="w-4 h-4 flex items-center justify-center rounded-full border transition
                                                {{ $task->isCompleted() ? 'bg-blue-600 border-blue-600 text-white' : 'border-slate-400 text-transparent hover:border-blue-500' }}"
                                                title="{{ $task->isCompleted() ? 'Снять отметку' : 'Отметить выполненной' }}">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                    </form>
                                    <a href="{{ route('calendar.tasks.edit', $task) }}"
                                       class="block truncate text-xs {{ $task->isCompleted() ? 'line-through text-slate-400' : 'text-slate-700 hover:text-blue-600' }}"
                                       title="{{ $task->title }}@if($task->user_id !== auth()->id()) · {{ $task->assignee?->name }}@endif">
                                        {{ $task->title }}@if($task->user_id !== auth()->id())<span class="text-slate-400"> · {{ \Illuminate\Support\Str::limit($task->assignee?->name, 12) }}</span>@endif
                                    </a>
                                </div>
                            @endforeach

                            @if ($cell['tasks']->count() > 3)
                                <span class="px-1.5 text-xs text-slate-500">Ещё {{ $cell['tasks']->count() - 3 }} задач</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            @endforeach
        </div>
    </div>
</div>

@include('calendar.partials.event-modal', ['rooms' => $rooms, 'staff' => $staff, 'tickets' => $tickets])
@include('calendar.partials.task-modal', ['staff' => $staff])

@push('scripts')
<script>
    // Перетаскивание событий между днями месяца. Повторяющиеся не таскаем —
    // у них draggable не выставлен.
    let calDraggedEventId = null;

    function calDragStart(e, eventId) {
        calDraggedEventId = eventId;
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', String(eventId));
        // Не даём клику-переходу по ссылке сработать после перетаскивания.
        e.target.addEventListener('click', preventOnceAfterDrag, true);
    }

    function preventOnceAfterDrag(e) {
        e.preventDefault();
        e.stopPropagation();
        e.currentTarget.removeEventListener('click', preventOnceAfterDrag, true);
    }

    function calDragEnd(e) {
        calDraggedEventId = null;
        document.querySelectorAll('[data-day-cell]').forEach((c) => c.classList.remove('ring-2', 'ring-blue-400', 'ring-inset'));
    }

    function calDragOver(e) {
        if (calDraggedEventId === null) return;
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        e.currentTarget.classList.add('ring-2', 'ring-blue-400', 'ring-inset');
    }

    function calDragLeave(e) {
        e.currentTarget.classList.remove('ring-2', 'ring-blue-400', 'ring-inset');
    }

    async function calDrop(e) {
        e.preventDefault();
        const cell = e.currentTarget;
        cell.classList.remove('ring-2', 'ring-blue-400', 'ring-inset');
        const eventId = calDraggedEventId;
        const date = cell.dataset.date;
        if (!eventId || !date) return;

        try {
            const res = await fetch(`/calendar/events/${eventId}/move`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ date }),
            });
            if (res.ok) {
                window.location.reload();
            } else {
                const data = await res.json().catch(() => ({}));
                alert(data.message || 'Не удалось перенести событие.');
            }
        } catch (err) {
            alert('Не удалось перенести событие.');
        }
    }
</script>
@endpush
@endsection
