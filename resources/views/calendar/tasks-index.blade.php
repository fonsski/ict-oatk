@extends('layouts.app')

@section('title', 'Задачи - Календарь')

@php
    $ruMonths = [1=>'янв',2=>'фев',3=>'мар',4=>'апр',5=>'мая',6=>'июн',7=>'июл',8=>'авг',9=>'сен',10=>'окт',11=>'ноя',12=>'дек'];
    $today = \Carbon\CarbonImmutable::today();
    $priorityDot = ['high' => 'bg-red-500', 'medium' => 'bg-amber-500', 'low' => 'bg-slate-400'];

    $taskDate = function ($task) use ($ruMonths, $today) {
        if (!$task->due_at) return null;
        $d = $task->due_at;
        if ($d->isSameDay($today)) {
            $label = 'Сегодня';
        } elseif ($d->isSameDay($today->addDay())) {
            $label = 'Завтра';
        } else {
            $label = $d->day . ' ' . $ruMonths[$d->month];
        }
        if (!$task->due_all_day) {
            $label .= ', ' . $d->format('H:i');
        }
        return ['label' => $label, 'overdue' => $d->lt($today->startOfDay()) && !$task->isCompleted()];
    };
@endphp

@section('content')
<div class="container-width section-padding">
    <div class="max-w-2xl mx-auto">
        {{-- Шапка: заголовок + переключатель Календарь/Задачи + создать --}}
        <div class="flex items-center justify-between mb-6 gap-4">
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">Задачи</h1>
            <div class="flex items-center gap-1">
                <a href="{{ route('calendar.index') }}" title="Календарь"
                   class="p-2 rounded-md text-slate-500 hover:bg-slate-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 2v4M8 2v4M3 10h18"/></svg>
                </a>
                <span class="p-2 rounded-md bg-blue-50 text-blue-600" title="Задачи">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 11l3 3L22 4M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
                </span>
                <button type="button" onclick="openTaskModal()" class="btn-primary py-1.5 px-4 text-sm ml-2">Создать</button>
            </div>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif

        <div class="card divide-y divide-slate-100">
            {{-- Активные задачи --}}
            @forelse ($pending as $task)
                @include('calendar.partials.task-row', ['task' => $task, 'taskDate' => $taskDate, 'priorityDot' => $priorityDot])
            @empty
                <div class="px-4 py-8 text-center text-sm text-slate-500">Активных задач нет.</div>
            @endforelse
        </div>

        {{-- Выполненные — сворачиваемо --}}
        @if ($completed->isNotEmpty())
            <div class="mt-4">
                <button type="button" onclick="document.getElementById('done-list').classList.toggle('hidden'); this.querySelector('svg').classList.toggle('rotate-90')"
                        class="flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-slate-800 px-1 py-2">
                    <svg class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    Выполнено ({{ $completed->count() }})
                </button>
                <div id="done-list" class="hidden card divide-y divide-slate-100 mt-1">
                    @foreach ($completed as $task)
                        @include('calendar.partials.task-row', ['task' => $task, 'taskDate' => $taskDate, 'priorityDot' => $priorityDot])
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

@include('calendar.partials.task-modal', ['staff' => \App\Models\User::query()->where('is_active', true)->whereHas('role', fn($q) => $q->whereIn('slug', ['admin','master','technician']))->where('id', '!=', auth()->id())->orderBy('name')->get(['id','name'])])
@endsection
