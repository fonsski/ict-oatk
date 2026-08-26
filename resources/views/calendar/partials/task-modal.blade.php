{{-- Модалка создания задачи. --}}
<div id="task-modal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="task-modal-title">
    <div class="absolute inset-0 bg-slate-900/40" onclick="closeTaskModal()"></div>

    <div class="relative min-h-screen flex items-start justify-center p-4 sm:pt-20">
        <div class="w-full max-w-md bg-white rounded-xl shadow-xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                <h2 id="task-modal-title" class="text-lg font-semibold text-slate-900">Новая задача</h2>
                <button type="button" onclick="closeTaskModal()" class="text-slate-400 hover:text-slate-600 p-1" aria-label="Закрыть">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('calendar.tasks.store') }}" class="px-6 py-5 space-y-4">
                @csrf

                <div>
                    <label for="tk-title" class="form-label">Название <span class="text-red-500">*</span></label>
                    <input type="text" id="tk-title" name="title" value="{{ old('title') }}" required
                           class="form-input @error('title') border-red-500 @enderror" placeholder="Например: Купить скетчбук A5">
                    @error('title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="tk-date" class="form-label">Дата <span class="text-red-500">*</span></label>
                        <input type="date" id="tk-date" name="due_date" class="form-input">
                        @error('due_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div id="tk-time-wrap">
                        <label for="tk-time" class="form-label">Время</label>
                        <input type="time" id="tk-time" name="due_time" class="form-input">
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" id="tk-all-day" name="due_all_day" value="1" checked onchange="toggleTaskTime(this.checked)"
                           class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    Весь день (без времени)
                </label>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="tk-priority" class="form-label">Приоритет</label>
                        <select id="tk-priority" name="priority" class="form-input">
                            @foreach (\App\Models\CalendarTask::PRIORITIES as $value => $label)
                                <option value="{{ $value }}" {{ old('priority', 'medium') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="tk-assignee" class="form-label">Исполнитель</label>
                        <select id="tk-assignee" name="user_id" class="form-input">
                            <option value="">Я</option>
                            @foreach ($staff as $person)
                                <option value="{{ $person->id }}" {{ old('user_id') == $person->id ? 'selected' : '' }}>{{ $person->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label for="tk-description" class="form-label">Описание</label>
                    <textarea id="tk-description" name="description" rows="2" class="form-input" placeholder="Необязательно">{{ old('description') }}</textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" onclick="closeTaskModal()" class="btn-outline">Отмена</button>
                    <button type="submit" class="btn-primary">Добавить задачу</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function openTaskModal(dateStr) {
        const modal = document.getElementById('task-modal');
        const d = dateStr ? new Date(dateStr) : new Date();
        const pad = (n) => String(n).padStart(2, '0');
        document.getElementById('tk-date').value = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
        toggleTaskTime(document.getElementById('tk-all-day').checked);
        modal.classList.remove('hidden');
        document.getElementById('tk-title').focus();
    }

    function closeTaskModal() {
        document.getElementById('task-modal').classList.add('hidden');
    }

    // «Весь день» скрывает и отключает поле времени.
    function toggleTaskTime(isAllDay) {
        document.getElementById('tk-time-wrap').classList.toggle('hidden', isAllDay);
        document.getElementById('tk-time').disabled = isAllDay;
    }

    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeTaskModal(); });

    // Инициализируем видимость поля времени согласно чекбоксу.
    document.addEventListener('DOMContentLoaded', () => toggleTaskTime(document.getElementById('tk-all-day').checked));

    // Ошибки валидации задачи (форма прислала due_date) — открываем эту модалку.
    @if ($errors->any() && old('due_date'))
        document.addEventListener('DOMContentLoaded', () => document.getElementById('task-modal').classList.remove('hidden'));
    @endif
</script>
@endpush
