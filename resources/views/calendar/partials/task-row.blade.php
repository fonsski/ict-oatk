{{--
    Строка задачи в списке. Ожидает: $task, $taskDate (замыкание), $priorityDot.
--}}
@php $date = $taskDate($task); @endphp
<div class="flex items-center gap-3 px-4 py-3">
    <form method="POST" action="{{ route('calendar.tasks.toggle', $task) }}" class="shrink-0">
        @csrf
        <button type="submit" class="w-5 h-5 flex items-center justify-center rounded-full border transition
                {{ $task->isCompleted() ? 'bg-blue-600 border-blue-600 text-white' : 'border-slate-400 text-transparent hover:border-blue-500' }}"
                title="{{ $task->isCompleted() ? 'Снять отметку' : 'Отметить выполненной' }}">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
        </button>
    </form>

    <span class="w-1.5 h-1.5 rounded-full shrink-0 {{ $priorityDot[$task->priority] ?? 'bg-slate-400' }}" title="Приоритет: {{ $task->priority_label }}"></span>

    <div class="min-w-0 flex-1">
        <a href="{{ route('calendar.tasks.edit', $task) }}"
           class="block truncate {{ $task->isCompleted() ? 'line-through text-slate-400' : 'text-slate-800 hover:text-blue-600' }}">
            {{ $task->title }}
        </a>
        @if ($task->user_id !== auth()->id())
            <span class="text-xs text-slate-400">Исполнитель: {{ $task->assignee?->name }}</span>
        @endif
    </div>

    @if ($date)
        <span class="shrink-0 text-xs {{ $date['overdue'] ? 'text-red-600 font-medium' : 'text-slate-500' }}">
            {{ $date['label'] }}
        </span>
    @endif
</div>
