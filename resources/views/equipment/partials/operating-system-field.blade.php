{{--
    Поле выбора ОС. Показывается только для категорий, у которых включён
    признак «требуется ОС» (ПК, ноутбуки) — переключается на лету по выбору
    категории. Ожидает: $operatingSystems, $selected (id или null).
--}}
<div class="mb-4 hidden" id="operating-system-field">
    <label for="operating_system_id" class="block text-slate-700 text-sm font-bold mb-2">
        Операционная система
    </label>
    <select name="operating_system_id" id="operating_system_id"
            class="w-full rounded-lg border-slate-300 px-3 py-2 text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('operating_system_id') border-red-500 @enderror">
        <option value="">Не указана</option>
        @foreach($operatingSystems->groupBy('family') as $family => $group)
            @if($family)
            <optgroup label="{{ $family }}">
                @foreach($group as $operatingSystem)
                <option value="{{ $operatingSystem->id }}" {{ (string) $selected === (string) $operatingSystem->id ? 'selected' : '' }}>
                    {{ $operatingSystem->name }}
                </option>
                @endforeach
            </optgroup>
            @else
                @foreach($group as $operatingSystem)
                <option value="{{ $operatingSystem->id }}" {{ (string) $selected === (string) $operatingSystem->id ? 'selected' : '' }}>
                    {{ $operatingSystem->name }}
                </option>
                @endforeach
            @endif
        @endforeach
    </select>
    <p class="text-sm text-slate-500 mt-1">
        Список настраивается в разделе
        <a href="{{ route('operating-systems.index') }}" class="text-blue-600 hover:text-blue-800">«Операционные системы»</a>
    </p>
    @error('operating_system_id')
        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
    @enderror
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const categorySelect = document.getElementById('category_id');
    const osField = document.getElementById('operating-system-field');
    const osSelect = document.getElementById('operating_system_id');
    if (!categorySelect || !osField) return;

    function toggleOsField() {
        const option = categorySelect.options[categorySelect.selectedIndex];
        const supported = option && option.dataset.hasOs === '1';

        osField.classList.toggle('hidden', !supported);
        // Скрытое поле не должно отправлять старое значение: для монитора
        // или ИБП операционной системы быть не может.
        if (!supported && osSelect) osSelect.value = '';
    }

    categorySelect.addEventListener('change', toggleOsField);
    toggleOsField();
});
</script>
@endpush
