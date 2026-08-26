@extends('layouts.app')

@section('title', 'Календарь — день - ICT Help')

@php
    $ruMonthsGen = [1=>'января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря'];
    $ruWeekdays = ['Понедельник','Вторник','Среда','Четверг','Пятница','Суббота','Воскресенье'];
    $title = $rangeStart->day . ' ' . $ruMonthsGen[$rangeStart->month] . ' ' . $rangeStart->year
           . ', ' . $ruWeekdays[$rangeStart->dayOfWeekIso - 1];
@endphp

@section('content')
<div class="container-width section-padding">
    @include('calendar.partials.toolbar', [
        'viewMode' => 'day',
        'title' => $title,
        'navPrev' => route('calendar.index', ['view' => 'day', 'date' => $prevDate]),
        'navNext' => route('calendar.index', ['view' => 'day', 'date' => $nextDate]),
        'navToday' => route('calendar.index', ['view' => 'day']),
        'anchor' => $rangeStart->toDateString(),
        'anchorMonth' => $rangeStart->format('Y-m'),
    ])

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    @include('calendar.partials.time-grid')
</div>

@include('calendar.partials.event-modal', ['rooms' => $rooms, 'staff' => $staff, 'tickets' => $tickets])
@include('calendar.partials.task-modal')
@endsection
