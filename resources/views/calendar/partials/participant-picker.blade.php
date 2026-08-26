{{--
    Выбор участников события из сотрудников.
    Ожидает: $staff (коллекция User), $selected (массив выбранных id).
--}}
@php $selected = $selected ?? []; @endphp

<div>
    <label class="form-label">Участники</label>
    @if ($staff->isEmpty())
        <p class="text-sm text-slate-500">Других сотрудников пока нет.</p>
    @else
        <div class="mt-1 max-h-40 overflow-y-auto rounded-lg border border-slate-200 divide-y divide-slate-100">
            @foreach ($staff as $person)
                <label class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-slate-50 cursor-pointer">
                    <input type="checkbox" name="participant_ids[]" value="{{ $person->id }}"
                           {{ in_array($person->id, $selected) ? 'checked' : '' }}
                           class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-slate-800">{{ $person->name }}</span>
                    @if ($person->position)
                        <span class="text-xs text-slate-400">· {{ $person->position }}</span>
                    @endif
                </label>
            @endforeach
        </div>
        <p class="mt-1 text-xs text-slate-500">Приглашённым уйдёт уведомление; они смогут ответить.</p>
    @endif
</div>
