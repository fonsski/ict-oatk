{{--
    Часовая сетка для видов «Неделя» и «День».
    Ожидает: $days — массив [date, allDay(collection), timed(array раскладки), tasks(collection)].
--}}
@php
    $hourPx = 48;          // высота часа
    $dayHeight = 24 * $hourPx;
    $ruWeekdaysShort = ['Пн','Вт','Ср','Чт','Пт','Сб','Вс'];
    // Насыщенная палитра для крупных блоков.
    $blockPalette = [
        'blue'   => 'bg-blue-100 border-blue-500 text-blue-900 hover:bg-blue-200',
        'green'  => 'bg-green-100 border-green-500 text-green-900 hover:bg-green-200',
        'red'    => 'bg-red-100 border-red-500 text-red-900 hover:bg-red-200',
        'amber'  => 'bg-amber-100 border-amber-500 text-amber-900 hover:bg-amber-200',
        'purple' => 'bg-purple-100 border-purple-500 text-purple-900 hover:bg-purple-200',
        'slate'  => 'bg-slate-200 border-slate-500 text-slate-900 hover:bg-slate-300',
    ];
    $hasAllDay = collect($days)->contains(fn ($d) => $d['allDay']->isNotEmpty() || $d['tasks']->isNotEmpty());
@endphp

<div class="card overflow-hidden">
    {{-- Шапка колонок с датами --}}
    <div class="flex border-b border-slate-200 bg-slate-50">
        <div class="w-14 shrink-0"></div>
        @foreach ($days as $d)
            @php $isToday = $d['date']->toDateString() === $todayDate; @endphp
            <a href="{{ route('calendar.index', ['view' => 'day', 'date' => $d['date']->toDateString()]) }}"
               class="flex-1 px-2 py-2 text-center border-l border-slate-100 hover:bg-slate-100 transition">
                <div class="text-xs text-slate-500 uppercase">{{ $ruWeekdaysShort[$d['date']->dayOfWeekIso - 1] }}</div>
                <div class="mt-0.5 inline-flex items-center justify-center w-7 h-7 rounded-full text-sm
                            {{ $isToday ? 'bg-blue-600 text-white font-semibold' : 'text-slate-700' }}">
                    {{ $d['date']->day }}
                </div>
            </a>
        @endforeach
    </div>

    {{-- Строка «весь день» и задачи --}}
    @if ($hasAllDay)
        <div class="flex border-b border-slate-200 bg-white">
            <div class="w-14 shrink-0 px-1 py-1 text-[10px] text-slate-400 text-right leading-tight">весь<br>день</div>
            @foreach ($days as $d)
                <div class="flex-1 border-l border-slate-100 p-1 space-y-0.5 min-h-[2rem]">
                    @foreach ($d['allDay'] as $occ)
                        <a href="{{ route('calendar.show', $occ->event) }}"
                           class="block truncate rounded px-1.5 py-0.5 text-xs font-medium {{ ($blockPalette[$occ->color()] ?? $blockPalette['blue']) }}"
                           title="{{ $occ->title }}">{{ $occ->title }}</a>
                    @endforeach
                    @foreach ($d['tasks'] as $task)
                        <div class="flex items-center gap-1">
                            <form method="POST" action="{{ route('calendar.tasks.toggle', $task) }}" class="shrink-0">
                                @csrf
                                <button type="submit" class="w-4 h-4 flex items-center justify-center rounded-full border transition
                                        {{ $task->isCompleted() ? 'bg-blue-600 border-blue-600 text-white' : 'border-slate-400 text-transparent hover:border-blue-500' }}"
                                        title="{{ $task->isCompleted() ? 'Снять отметку' : 'Отметить выполненной' }}">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </form>
                            <a href="{{ route('calendar.tasks.edit', $task) }}"
                               class="truncate text-xs {{ $task->isCompleted() ? 'line-through text-slate-400' : 'text-slate-700 hover:text-blue-600' }}"
                               title="{{ $task->title }}">{{ $task->title }}</a>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    @endif

    {{-- Часовая сетка со скроллом --}}
    <div class="overflow-y-auto" style="max-height: 70vh;" id="time-grid-scroll">
        <div class="flex" style="height: {{ $dayHeight }}px;">
            {{-- Шкала часов --}}
            <div class="w-14 shrink-0 relative">
                @for ($h = 0; $h < 24; $h++)
                    <div class="absolute right-1 text-[11px] text-slate-400 -translate-y-1/2" style="top: {{ $h * $hourPx }}px;">
                        {{ sprintf('%02d:00', $h) }}
                    </div>
                @endfor
            </div>

            {{-- Колонки дней --}}
            @foreach ($days as $d)
                <div class="flex-1 relative border-l border-slate-100">
                    {{-- Часовые линии --}}
                    @for ($h = 0; $h < 24; $h++)
                        <div class="absolute left-0 right-0 border-t border-slate-100" style="top: {{ $h * $hourPx }}px;"></div>
                    @endfor

                    {{-- События с временем --}}
                    @foreach ($d['timed'] as $item)
                        @php
                            $occ = $item['occ'];
                            $top = $item['startMin'] / 60 * $hourPx;
                            $height = max(18, ($item['endMin'] - $item['startMin']) / 60 * $hourPx - 2);
                            $widthPct = 100 / $item['cols'];
                            $leftPct = $item['col'] * $widthPct;
                        @endphp
                        <a href="{{ route('calendar.show', $occ->event) }}"
                           class="absolute rounded border-l-4 px-1.5 py-0.5 overflow-hidden text-xs {{ ($blockPalette[$occ->color()] ?? $blockPalette['blue']) }}"
                           style="top: {{ $top }}px; height: {{ $height }}px; left: calc({{ $leftPct }}% + 2px); width: calc({{ $widthPct }}% - 4px);"
                           title="{{ $occ->title }} ({{ $occ->startsAt->format('H:i') }}–{{ $occ->endsAt->format('H:i') }})">
                            <span class="font-medium">{{ $occ->startsAt->format('H:i') }}</span>
                            <span class="block truncate">{{ $occ->title }}</span>
                        </a>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Прокручиваем сетку к рабочему утру, чтобы не начинать с полуночи.
    document.addEventListener('DOMContentLoaded', () => {
        const scroll = document.getElementById('time-grid-scroll');
        if (scroll) scroll.scrollTop = 7 * {{ $hourPx }};
    });
</script>
@endpush
