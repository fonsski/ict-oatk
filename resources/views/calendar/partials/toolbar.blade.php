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

        {{-- Переключатель Календарь / Задачи и настройки отображения --}}
        <div class="inline-flex items-center gap-1">
            <a href="{{ route('calendar.index') }}" title="Календарь"
               class="p-2 rounded-md {{ ($viewMode ?? '') === 'tasks' ? 'text-slate-500 hover:bg-slate-100' : 'bg-blue-50 text-blue-600' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 2v4M8 2v4M3 10h18"/></svg>
            </a>
            <a href="{{ route('calendar.tasks.index') }}" title="Задачи"
               class="p-2 rounded-md {{ ($viewMode ?? '') === 'tasks' ? 'bg-blue-50 text-blue-600' : 'text-slate-500 hover:bg-slate-100' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 11l3 3L22 4M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
            </a>

            <div class="relative" id="cal-settings-menu">
                <button type="button" onclick="toggleCalSettings()" class="p-2 rounded-md text-slate-500 hover:text-slate-700 hover:bg-slate-100" title="Настройки отображения" aria-label="Настройки">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </button>
                <div id="cal-settings-items" class="hidden absolute right-0 mt-1 w-64 bg-white rounded-md shadow-lg border border-slate-200 z-30 py-2">
                    <label class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 cursor-pointer">
                        <input type="checkbox" id="pref-show-completed" checked class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        Показывать выполненные задачи
                    </label>
                    <label class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 cursor-pointer">
                        <input type="checkbox" id="pref-show-declined" checked class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        Показывать отклонённые мероприятия
                    </label>
                </div>
            </div>
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

<style>
    /* Скрытие по настройкам отображения. */
    html.hide-completed-tasks [data-task-done] { display: none; }
    html.hide-declined-events [data-event-declined] { display: none; }
</style>

@push('scripts')
<script>
    function toggleCreateMenu() { document.getElementById('create-menu-items').classList.toggle('hidden'); }
    function closeCreateMenu() { const m = document.getElementById('create-menu-items'); if (m) m.classList.add('hidden'); }

    function toggleCalSettings() { document.getElementById('cal-settings-items').classList.toggle('hidden'); }
    function closeCalSettings() { const m = document.getElementById('cal-settings-items'); if (m) m.classList.add('hidden'); }

    document.addEventListener('click', (e) => {
        if (!e.target.closest('#create-menu')) closeCreateMenu();
        if (!e.target.closest('#cal-settings-menu')) closeCalSettings();
    });

    // Настройки отображения — в localStorage, применяются мгновенно ко всем
    // видам без перезагрузки.
    function bindDisplayPref(storageKey, htmlClass, boxId) {
        const apply = () => {
            const hide = localStorage.getItem(storageKey) === '1';
            document.documentElement.classList.toggle(htmlClass, hide);
            const box = document.getElementById(boxId);
            if (box) box.checked = !hide;
        };
        apply();
        const box = document.getElementById(boxId);
        if (box) box.addEventListener('change', () => {
            localStorage.setItem(storageKey, box.checked ? '0' : '1');
            apply();
        });
    }
    document.addEventListener('DOMContentLoaded', () => {
        bindDisplayPref('cal_hide_completed_tasks', 'hide-completed-tasks', 'pref-show-completed');
        bindDisplayPref('cal_hide_declined_events', 'hide-declined-events', 'pref-show-declined');
    });
</script>
@endpush
