{{--
    Блок настройки повторения события.
    Необязательный $event — для предзаполнения на форме правки.
--}}
@php
    $rEvent = $event ?? null;
    $freqVal = old('recurrence_freq', $rEvent?->recurrence_freq ?? '');
    $bydayVal = old('recurrence_byday', $rEvent && $rEvent->recurrence_byday ? explode(',', $rEvent->recurrence_byday) : []);
    if (old('recurrence_end_mode')) {
        $endMode = old('recurrence_end_mode');
    } elseif ($rEvent && $rEvent->recurrence_until) {
        $endMode = 'until';
    } elseif ($rEvent && $rEvent->recurrence_count) {
        $endMode = 'count';
    } else {
        $endMode = 'never';
    }
    $untilVal = old('recurrence_until', $rEvent?->recurrence_until?->format('Y-m-d'));
    $countVal = old('recurrence_count', $rEvent?->recurrence_count);
    $weekdays = ['MO'=>'Пн','TU'=>'Вт','WE'=>'Ср','TH'=>'Чт','FR'=>'Пт','SA'=>'Сб','SU'=>'Вс'];
@endphp

<div>
    <label for="rec-freq" class="form-label">Повторение</label>
    <select id="rec-freq" name="recurrence_freq" class="form-input" onchange="onRecurrenceFreqChange()">
        <option value="">Не повторять</option>
        @foreach (\App\Models\CalendarEvent::FREQUENCIES as $value => $label)
            <option value="{{ $value }}" {{ $freqVal === $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
</div>

{{-- Дни недели — только для еженедельного повтора --}}
<div id="rec-weekdays" class="{{ $freqVal === 'weekly' ? '' : 'hidden' }}">
    <label class="form-label">Дни недели</label>
    <div class="flex flex-wrap gap-1 mt-1">
        @foreach ($weekdays as $code => $short)
            <label class="cursor-pointer">
                <input type="checkbox" name="recurrence_byday[]" value="{{ $code }}"
                       {{ in_array($code, $bydayVal) ? 'checked' : '' }} class="sr-only peer">
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-full text-sm border border-slate-300 text-slate-600
                             peer-checked:bg-blue-600 peer-checked:border-blue-600 peer-checked:text-white transition">{{ $short }}</span>
            </label>
        @endforeach
    </div>
    <p class="mt-1 text-xs text-slate-500">Если не выбрать ни одного — берётся день недели даты начала.</p>
</div>

{{-- Окончание повтора --}}
<div id="rec-end" class="{{ $freqVal ? '' : 'hidden' }} space-y-2">
    <label class="form-label">Заканчивается</label>
    <label class="flex items-center gap-2 text-sm text-slate-700">
        <input type="radio" name="recurrence_end_mode" value="never" {{ $endMode === 'never' ? 'checked' : '' }}
               onchange="onRecurrenceEndModeChange()" class="text-blue-600 focus:ring-blue-500">
        Никогда
    </label>
    <div class="flex items-center gap-2">
        <label class="flex items-center gap-2 text-sm text-slate-700 shrink-0">
            <input type="radio" name="recurrence_end_mode" value="until" {{ $endMode === 'until' ? 'checked' : '' }}
                   onchange="onRecurrenceEndModeChange()" class="text-blue-600 focus:ring-blue-500">
            До даты
        </label>
        <input type="date" name="recurrence_until" id="rec-until" value="{{ $untilVal }}"
               class="form-input py-1 text-sm {{ $endMode === 'until' ? '' : 'hidden' }}">
    </div>
    <div class="flex items-center gap-2">
        <label class="flex items-center gap-2 text-sm text-slate-700 shrink-0">
            <input type="radio" name="recurrence_end_mode" value="count" {{ $endMode === 'count' ? 'checked' : '' }}
                   onchange="onRecurrenceEndModeChange()" class="text-blue-600 focus:ring-blue-500">
            После
        </label>
        <input type="number" name="recurrence_count" id="rec-count" min="1" max="365" value="{{ $countVal }}"
               class="form-input py-1 text-sm w-20 {{ $endMode === 'count' ? '' : 'hidden' }}" placeholder="10">
        <span class="text-sm text-slate-500 {{ $endMode === 'count' ? '' : 'hidden' }}" id="rec-count-label">повторов</span>
    </div>
    @error('recurrence_until')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
    @error('recurrence_count')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
</div>

@push('scripts')
<script>
    function onRecurrenceFreqChange() {
        const freq = document.getElementById('rec-freq').value;
        document.getElementById('rec-weekdays').classList.toggle('hidden', freq !== 'weekly');
        document.getElementById('rec-end').classList.toggle('hidden', !freq);
    }

    function onRecurrenceEndModeChange() {
        const mode = document.querySelector('input[name="recurrence_end_mode"]:checked')?.value;
        document.getElementById('rec-until').classList.toggle('hidden', mode !== 'until');
        const showCount = mode === 'count';
        document.getElementById('rec-count').classList.toggle('hidden', !showCount);
        document.getElementById('rec-count-label').classList.toggle('hidden', !showCount);
    }
</script>
@endpush
