@extends('layouts.app')

@section('title', 'Редактирование события - Календарь')

@section('content')
<div class="container-width section-padding">
    <div class="max-w-lg mx-auto">
        <a href="{{ route('calendar.show', $event) }}"
           class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700 mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Назад
        </a>

        <div class="card p-6">
            <h1 class="text-xl font-bold text-slate-900 mb-5">Редактирование события</h1>

            <form method="POST" action="{{ route('calendar.update', $event) }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="title" class="form-label">Название <span class="text-red-500">*</span></label>
                    <input type="text" id="title" name="title" value="{{ old('title', $event->title) }}" required
                           class="form-input @error('title') border-red-500 @enderror">
                    @error('title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" id="all_day" name="all_day" value="1" {{ old('all_day', $event->all_day) ? 'checked' : '' }}
                           onchange="toggleAllDayEdit(this.checked)"
                           class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    Весь день
                </label>

                <div id="edit-datetime-fields" class="grid grid-cols-1 sm:grid-cols-2 gap-3 {{ old('all_day', $event->all_day) ? 'hidden' : '' }}">
                    <div>
                        <label for="starts_at" class="form-label">Начало</label>
                        <input type="datetime-local" id="starts_at" name="starts_at"
                               value="{{ old('starts_at', $event->starts_at->format('Y-m-d\TH:i')) }}" class="form-input"
                               {{ old('all_day', $event->all_day) ? 'disabled' : '' }}>
                    </div>
                    <div>
                        <label for="ends_at" class="form-label">Окончание</label>
                        <input type="datetime-local" id="ends_at" name="ends_at"
                               value="{{ old('ends_at', $event->ends_at->format('Y-m-d\TH:i')) }}" class="form-input"
                               {{ old('all_day', $event->all_day) ? 'disabled' : '' }}>
                    </div>
                </div>

                <div id="edit-date-fields" class="grid grid-cols-1 sm:grid-cols-2 gap-3 {{ old('all_day', $event->all_day) ? '' : 'hidden' }}">
                    <div>
                        <label for="starts_date" class="form-label">Дата начала</label>
                        <input type="date" id="starts_date" name="starts_date"
                               value="{{ old('starts_date', $event->starts_at->format('Y-m-d')) }}" class="form-input"
                               {{ old('all_day', $event->all_day) ? '' : 'disabled' }}>
                    </div>
                    <div>
                        <label for="ends_date" class="form-label">Дата окончания</label>
                        <input type="date" id="ends_date" name="ends_date"
                               value="{{ old('ends_date', $event->ends_at->format('Y-m-d')) }}" class="form-input"
                               {{ old('all_day', $event->all_day) ? '' : 'disabled' }}>
                    </div>
                </div>

                @error('starts_at')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                @error('ends_at')<p class="text-sm text-red-600">{{ $message }}</p>@enderror

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="room_id" class="form-label">Кабинет</label>
                        <select id="room_id" name="room_id" class="form-input">
                            <option value="">— не указан —</option>
                            @foreach ($rooms as $room)
                                <option value="{{ $room->id }}" {{ old('room_id', $event->room_id) == $room->id ? 'selected' : '' }}>
                                    {{ $room->number }}{{ $room->name ? ' — ' . $room->name : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="location" class="form-label">Место (текстом)</label>
                        <input type="text" id="location" name="location" value="{{ old('location', $event->location) }}" class="form-input">
                    </div>
                </div>

                <div>
                    <label class="form-label">Цвет метки</label>
                    <div class="flex items-center gap-2 mt-1">
                        @foreach (\App\Models\CalendarEvent::COLORS as $c)
                            @php $dot = ['blue'=>'bg-blue-500','green'=>'bg-green-500','red'=>'bg-red-500','amber'=>'bg-amber-500','purple'=>'bg-purple-500','slate'=>'bg-slate-500'][$c]; @endphp
                            <label class="cursor-pointer">
                                <input type="radio" name="color" value="{{ $c }}" class="sr-only peer" {{ old('color', $event->color) === $c ? 'checked' : '' }}>
                                <span class="block w-6 h-6 rounded-full {{ $dot }} ring-offset-2 peer-checked:ring-2 ring-slate-400"></span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label for="description" class="form-label">Описание</label>
                    <textarea id="description" name="description" rows="2" class="form-input">{{ old('description', $event->description) }}</textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('calendar.show', $event) }}" class="btn-outline">Отмена</a>
                    <button type="submit" class="btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function toggleAllDayEdit(isAllDay) {
        document.getElementById('edit-datetime-fields').classList.toggle('hidden', isAllDay);
        document.getElementById('edit-date-fields').classList.toggle('hidden', !isAllDay);
        document.getElementById('starts_at').disabled = isAllDay;
        document.getElementById('ends_at').disabled = isAllDay;
        document.getElementById('starts_date').disabled = !isAllDay;
        document.getElementById('ends_date').disabled = !isAllDay;
    }
</script>
@endpush
@endsection
