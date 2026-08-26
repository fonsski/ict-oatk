@extends('layouts.app')

@section('title', 'Календарь — неделя - ICT Help')

@php
    $ruMonthsGen = [1=>'января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря'];
    // Заголовок недели: «18–24 августа 2026» или «28 авг – 3 сен 2026».
    if ($rangeStart->month === $rangeEnd->month) {
        $title = $rangeStart->day . '–' . $rangeEnd->day . ' ' . $ruMonthsGen[$rangeStart->month] . ' ' . $rangeStart->year;
    } else {
        $title = $rangeStart->day . ' ' . mb_substr($ruMonthsGen[$rangeStart->month], 0, 3)
               . ' – ' . $rangeEnd->day . ' ' . mb_substr($ruMonthsGen[$rangeEnd->month], 0, 3) . ' ' . $rangeEnd->year;
    }
@endphp

@section('content')
<div class="container-width section-padding">
    @include('calendar.partials.toolbar', [
        'viewMode' => 'week',
        'title' => $title,
        'navPrev' => route('calendar.index', ['view' => 'week', 'date' => $prevDate]),
        'navNext' => route('calendar.index', ['view' => 'week', 'date' => $nextDate]),
        'navToday' => route('calendar.index', ['view' => 'week']),
        'anchor' => $rangeStart->toDateString(),
        'anchorMonth' => $rangeStart->format('Y-m'),
    ])

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    @include('calendar.partials.time-grid')
</div>

@include('calendar.partials.event-modal', ['rooms' => $rooms, 'staff' => $staff, 'tickets' => $tickets])
@include('calendar.partials.task-modal', ['staff' => $staff])
@endsection
