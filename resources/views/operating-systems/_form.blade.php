{{-- Общие поля формы ОС. Ожидает необязательный $operatingSystem. --}}
@php $operatingSystem = $operatingSystem ?? null; @endphp

<div class="mb-4">
    <label for="name" class="block text-slate-700 text-sm font-bold mb-2">Название *</label>
    <input type="text" name="name" id="name" required minlength="2" maxlength="255"
           class="w-full rounded-lg border-slate-300 px-3 py-2 text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 @enderror"
           value="{{ old('name', $operatingSystem->name ?? '') }}"
           placeholder="Например: Windows 11 Pro">
    @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
    <div>
        <label for="family" class="block text-slate-700 text-sm font-bold mb-2">Семейство</label>
        <input type="text" name="family" id="family" maxlength="100" list="os-families"
               class="w-full rounded-lg border-slate-300 px-3 py-2 text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('family') border-red-500 @enderror"
               value="{{ old('family', $operatingSystem->family ?? '') }}"
               placeholder="Windows / Linux / macOS">
        <datalist id="os-families">
            <option value="Windows"></option>
            <option value="Linux"></option>
            <option value="macOS"></option>
        </datalist>
        <p class="text-xs text-slate-500 mt-1">Используется для группировки в списках</p>
        @error('family')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="sort_order" class="block text-slate-700 text-sm font-bold mb-2">Порядок сортировки</label>
        <input type="number" name="sort_order" id="sort_order" min="0"
               class="w-full rounded-lg border-slate-300 px-3 py-2 text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('sort_order') border-red-500 @enderror"
               value="{{ old('sort_order', $operatingSystem->sort_order ?? 0) }}">
        <p class="text-xs text-slate-500 mt-1">Чем меньше число, тем выше в списке</p>
        @error('sort_order')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>
</div>

<div class="mb-4">
    <label for="description" class="block text-slate-700 text-sm font-bold mb-2">Описание</label>
    <textarea name="description" id="description" rows="3" maxlength="1000"
              class="w-full rounded-lg border-slate-300 px-3 py-2 text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('description') border-red-500 @enderror">{{ old('description', $operatingSystem->description ?? '') }}</textarea>
    @error('description')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
</div>

<div class="mb-6">
    <label class="flex items-center text-slate-700 text-sm font-bold">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" class="mr-2"
               {{ old('is_active', $operatingSystem->is_active ?? true) ? 'checked' : '' }}>
        Активна
    </label>
    <p class="text-xs text-slate-500 mt-1">Неактивные ОС не предлагаются при выборе в карточке оборудования, но остаются у уже привязанной техники</p>
</div>
