{{-- Модалка создания события. Дата подставляется при клике по дню сетки. --}}
<div id="event-modal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="event-modal-title">
    <div class="absolute inset-0 bg-slate-900/40" onclick="closeEventModal()"></div>

    <div class="relative min-h-screen flex items-start justify-center p-4 sm:pt-20">
        <div class="w-full max-w-lg bg-white rounded-xl shadow-xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                <h2 id="event-modal-title" class="text-lg font-semibold text-slate-900">Новое событие</h2>
                <button type="button" onclick="closeEventModal()" class="text-slate-400 hover:text-slate-600 p-1" aria-label="Закрыть">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('calendar.events.store') }}" class="px-6 py-5 space-y-4">
                @csrf

                <div>
                    <label for="ev-title" class="form-label">Название <span class="text-red-500">*</span></label>
                    <input type="text" id="ev-title" name="title" value="{{ old('title') }}" required
                           class="form-input @error('title') border-red-500 @enderror" placeholder="Например: Планёрка отдела">
                    @error('title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" id="ev-all-day" name="all_day" value="1" onchange="toggleAllDay(this.checked)"
                           class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    Весь день
                </label>

                {{-- Поля с временем (по умолчанию) --}}
                <div id="ev-datetime-fields" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="ev-starts-at" class="form-label">Начало <span class="text-red-500">*</span></label>
                        <input type="datetime-local" id="ev-starts-at" name="starts_at" class="form-input">
                    </div>
                    <div>
                        <label for="ev-ends-at" class="form-label">Окончание <span class="text-red-500">*</span></label>
                        <input type="datetime-local" id="ev-ends-at" name="ends_at" class="form-input">
                    </div>
                </div>

                {{-- Поля без времени (для «весь день») --}}
                <div id="ev-date-fields" class="grid grid-cols-1 sm:grid-cols-2 gap-3 hidden">
                    <div>
                        <label for="ev-starts-date" class="form-label">Дата начала <span class="text-red-500">*</span></label>
                        <input type="date" id="ev-starts-date" name="starts_date" class="form-input" disabled>
                    </div>
                    <div>
                        <label for="ev-ends-date" class="form-label">Дата окончания</label>
                        <input type="date" id="ev-ends-date" name="ends_date" class="form-input" disabled>
                    </div>
                </div>

                @error('starts_at')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                @error('ends_at')<p class="text-sm text-red-600">{{ $message }}</p>@enderror

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="ev-room" class="form-label">Кабинет</label>
                        <select id="ev-room" name="room_id" class="form-input">
                            <option value="">— не указан —</option>
                            @foreach ($rooms as $room)
                                <option value="{{ $room->id }}" {{ old('room_id') == $room->id ? 'selected' : '' }}>
                                    {{ $room->number }}{{ $room->name ? ' — ' . $room->name : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="ev-location" class="form-label">Место (текстом)</label>
                        <input type="text" id="ev-location" name="location" value="{{ old('location') }}"
                               class="form-input" placeholder="Актовый зал, онлайн…">
                    </div>
                </div>

                <div>
                    <label class="form-label">Цвет метки</label>
                    <div class="flex items-center gap-2 mt-1">
                        @foreach (\App\Models\CalendarEvent::COLORS as $i => $c)
                            @php
                                $dot = [
                                    'blue'=>'bg-blue-500','green'=>'bg-green-500','red'=>'bg-red-500',
                                    'amber'=>'bg-amber-500','purple'=>'bg-purple-500','slate'=>'bg-slate-500',
                                ][$c];
                            @endphp
                            <label class="cursor-pointer">
                                <input type="radio" name="color" value="{{ $c }}" class="sr-only peer" {{ old('color', 'blue') === $c ? 'checked' : '' }}>
                                <span class="block w-6 h-6 rounded-full {{ $dot }} ring-offset-2 peer-checked:ring-2 ring-slate-400"></span>
                            </label>
                        @endforeach
                    </div>
                </div>

                @include('calendar.partials.participant-picker', ['staff' => $staff, 'selected' => old('participant_ids', [])])

                <div>
                    <label for="ev-description" class="form-label">Описание</label>
                    <textarea id="ev-description" name="description" rows="2" class="form-input" placeholder="Необязательно">{{ old('description') }}</textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" onclick="closeEventModal()" class="btn-outline">Отмена</button>
                    <button type="submit" class="btn-primary">Создать событие</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function openEventModal(dateStr) {
        const modal = document.getElementById('event-modal');
        const startsAt = document.getElementById('ev-starts-at');
        const endsAt = document.getElementById('ev-ends-at');
        const startsDate = document.getElementById('ev-starts-date');
        const endsDate = document.getElementById('ev-ends-date');

        // База — переданный день или сегодня, время по умолчанию 10:00–11:00.
        const base = dateStr ? new Date(dateStr + 'T10:00') : (() => { const d = new Date(); d.setHours(10, 0, 0, 0); return d; })();
        const pad = (n) => String(n).padStart(2, '0');
        const ymd = `${base.getFullYear()}-${pad(base.getMonth() + 1)}-${pad(base.getDate())}`;
        const hm = `${pad(base.getHours())}:${pad(base.getMinutes())}`;
        const endHm = `${pad(base.getHours() + 1)}:${pad(base.getMinutes())}`;

        startsAt.value = `${ymd}T${hm}`;
        endsAt.value = `${ymd}T${endHm}`;
        startsDate.value = ymd;
        endsDate.value = ymd;

        modal.classList.remove('hidden');
        document.getElementById('ev-title').focus();
    }

    function closeEventModal() {
        document.getElementById('event-modal').classList.add('hidden');
    }

    // «Весь день» переключает набор полей и отключает скрытый, чтобы он не
    // уходил на сервер и не мешал валидации.
    function toggleAllDay(isAllDay) {
        const dt = document.getElementById('ev-datetime-fields');
        const d = document.getElementById('ev-date-fields');
        dt.classList.toggle('hidden', isAllDay);
        d.classList.toggle('hidden', !isAllDay);
        document.getElementById('ev-starts-at').disabled = isAllDay;
        document.getElementById('ev-ends-at').disabled = isAllDay;
        document.getElementById('ev-starts-date').disabled = !isAllDay;
        document.getElementById('ev-ends-date').disabled = !isAllDay;
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeEventModal();
    });

    // Если сервер вернул ошибки валидации — снова показываем модалку.
    @if ($errors->any())
        document.addEventListener('DOMContentLoaded', () => document.getElementById('event-modal').classList.remove('hidden'));
    @endif
</script>
@endpush
