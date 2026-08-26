{{--
    Общая шапка календаря: заголовок, навигация, переключатель вида, «Создать».
    Ожидает: $viewMode, $title, $navPrev, $navNext, $navToday, $anchor (Y-m-d),
             $anchorMonth (Y-m).
--}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
    <div class="flex items-center gap-3">
        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">{{ $title }}</h1>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        {{-- Переключатель вида --}}
        <div class="inline-flex rounded-lg border border-slate-200 overflow-hidden">
            @php
                $tabs = [
                    'month' => ['Месяц', route('calendar.index', ['view' => 'month', 'month' => $anchorMonth])],
                    'week'  => ['Неделя', route('calendar.index', ['view' => 'week', 'date' => $anchor])],
                    'day'   => ['День', route('calendar.index', ['view' => 'day', 'date' => $anchor])],
                ];
            @endphp
            @foreach ($tabs as $key => [$label, $url])
                <a href="{{ $url }}"
                   class="px-3 py-1.5 text-sm font-medium transition
                          {{ $viewMode === $key ? 'bg-blue-600 text-white' : 'text-slate-600 hover:bg-slate-50' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <div class="flex items-center gap-1">
            <a href="{{ $navPrev }}" class="p-2 rounded-md text-slate-500 hover:text-slate-700 hover:bg-slate-100" aria-label="Назад">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <a href="{{ $navToday }}" class="btn-outline py-1.5 px-4 text-sm">Сегодня</a>
            <a href="{{ $navNext }}" class="p-2 rounded-md text-slate-500 hover:text-slate-700 hover:bg-slate-100" aria-label="Вперёд">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        {{-- Создать: событие или задача --}}
        <div class="relative" id="create-menu">
            <button type="button" onclick="toggleCreateMenu()" class="btn-primary py-1.5 px-4 text-sm flex items-center gap-1">
                Создать
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div id="create-menu-items" class="hidden absolute right-0 mt-1 w-44 bg-white rounded-md shadow-lg border border-slate-200 z-30 py-1">
                <button type="button" onclick="closeCreateMenu(); openEventModal()" class="block w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Событие</button>
                <button type="button" onclick="closeCreateMenu(); openTaskModal()" class="block w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Задачу</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function toggleCreateMenu() { document.getElementById('create-menu-items').classList.toggle('hidden'); }
    function closeCreateMenu() { const m = document.getElementById('create-menu-items'); if (m) m.classList.add('hidden'); }
    document.addEventListener('click', (e) => { if (!e.target.closest('#create-menu')) closeCreateMenu(); });
</script>
@endpush
