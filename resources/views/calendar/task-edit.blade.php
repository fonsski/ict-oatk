@extends('layouts.app')

@section('title', 'Задача - Календарь')

@section('content')
<div class="container-width section-padding">
    <div class="max-w-md mx-auto">
        <a href="{{ route('calendar.index', ['month' => $task->due_at?->format('Y-m')]) }}"
           class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700 mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            К календарю
        </a>

        @if (session('success'))
            <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif

        <div class="card p-6">
            <div class="flex items-center justify-between mb-5">
                <h1 class="text-xl font-bold text-slate-900">Задача</h1>
                <form method="POST" action="{{ route('calendar.tasks.toggle', $task) }}">
                    @csrf
                    <button type="submit" class="text-sm py-1 px-3 rounded-md border transition
                            {{ $task->isCompleted() ? 'bg-blue-600 border-blue-600 text-white' : 'border-slate-300 text-slate-700 hover:bg-slate-50' }}">
                        {{ $task->isCompleted() ? 'Выполнена ✓' : 'Отметить выполненной' }}
                    </button>
                </form>
            </div>

            <form method="POST" action="{{ route('calendar.tasks.update', $task) }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="title" class="form-label">Название <span class="text-red-500">*</span></label>
                    <input type="text" id="title" name="title" value="{{ old('title', $task->title) }}" required
                           class="form-input @error('title') border-red-500 @enderror">
                    @error('title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="due_date" class="form-label">Дата <span class="text-red-500">*</span></label>
                        <input type="date" id="due_date" name="due_date"
                               value="{{ old('due_date', $task->due_at?->format('Y-m-d')) }}" class="form-input">
                        @error('due_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div id="edit-time-wrap" class="{{ $task->due_all_day ? 'hidden' : '' }}">
                        <label for="due_time" class="form-label">Время</label>
                        <input type="time" id="due_time" name="due_time"
                               value="{{ old('due_time', $task->due_all_day ? '' : $task->due_at?->format('H:i')) }}" class="form-input"
                               {{ $task->due_all_day ? 'disabled' : '' }}>
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" id="due_all_day" name="due_all_day" value="1" {{ old('due_all_day', $task->due_all_day) ? 'checked' : '' }}
                           onchange="toggleEditTaskTime(this.checked)"
                           class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    Весь день (без времени)
                </label>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="priority" class="form-label">Приоритет</label>
                        <select id="priority" name="priority" class="form-input">
                            @foreach (\App\Models\CalendarTask::PRIORITIES as $value => $label)
                                <option value="{{ $value }}" {{ old('priority', $task->priority) === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="user_id" class="form-label">Исполнитель</label>
                        <select id="user_id" name="user_id" class="form-input">
                            @foreach ($staff as $person)
                                <option value="{{ $person->id }}" {{ old('user_id', $task->user_id) == $person->id ? 'selected' : '' }}>{{ $person->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <p class="text-xs text-slate-500">
                    Автор: {{ $task->creator?->name ?? '—' }}
                </p>

                <div>
                    <label for="description" class="form-label">Описание</label>
                    <textarea id="description" name="description" rows="2" class="form-input">{{ old('description', $task->description) }}</textarea>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <button type="submit" class="btn-primary">Сохранить</button>
                </div>
            </form>

            <form method="POST" action="{{ route('calendar.tasks.destroy', $task) }}"
                  onsubmit="return confirm('Удалить задачу?')" class="mt-4 pt-4 border-t border-slate-100">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-sm text-red-600 hover:text-red-700 hover:bg-red-50 rounded-md py-1.5 px-3">Удалить задачу</button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function toggleEditTaskTime(isAllDay) {
        document.getElementById('edit-time-wrap').classList.toggle('hidden', isAllDay);
        document.getElementById('due_time').disabled = isAllDay;
    }
</script>
@endpush
@endsection
