{{--
    Подтверждение удаления заявки.

    Одного «вы уверены?» мало: пункт легко нажать не глядя. Поэтому
    показываем, что именно удаляется — номер, заголовок и начало текста, —
    и просим набрать слово подтверждения. То же слово проверяется на
    сервере, так что обойти окно через прямой запрос не выйдет.

    Ожидает:
      $ticket    — удаляемая заявка
      $action    — URL формы (tickets.destroy или tickets.force-delete)
      $permanent — true для безвозвратного удаления
      $trigger   — подпись кнопки, открывающей окно
--}}
@php
    $permanent = $permanent ?? false;
    $modalId = 'delete-ticket-modal-' . $ticket->id . ($permanent ? '-force' : '');
    $keyword = \App\Http\Controllers\TicketController::DELETE_CONFIRMATION;
    $excerpt = \Illuminate\Support\Str::limit((string) $ticket->description, 200);
@endphp

<button type="button"
        class="text-red-600 hover:text-red-800 text-sm font-medium"
        onclick="document.getElementById('{{ $modalId }}').classList.remove('hidden')">
    {{ $trigger ?? 'Удалить заявку' }}
</button>

<div id="{{ $modalId }}" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[100] p-4">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-lg">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-1">
                {{ $permanent ? 'Удалить заявку безвозвратно' : 'Удалить заявку' }}
            </h3>
            <p class="text-sm text-gray-600 mb-4">
                {{ $permanent
                    ? 'Заявка и все её комментарии будут стёрты без возможности восстановления.'
                    : 'Заявка попадёт в корзину — оттуда её ещё можно вернуть.' }}
            </p>

            {{-- Показываем, что именно удаляется, чтобы не снести не ту заявку --}}
            <div class="bg-gray-50 border border-gray-200 rounded-md p-4 mb-4">
                <div class="text-xs text-gray-500 mb-1">Заявка #{{ $ticket->id }}</div>
                <div class="font-medium text-gray-900 mb-2">{{ $ticket->title }}</div>
                @if($excerpt)
                <div class="text-sm text-gray-600 whitespace-pre-line">{{ $excerpt }}</div>
                @endif
                @if($ticket->reporter_name)
                <div class="text-xs text-gray-500 mt-2">Заявитель: {{ $ticket->reporter_name }}</div>
                @endif
            </div>

            <form method="POST" action="{{ $action }}">
                @csrf
                @method('DELETE')

                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Введите слово <span class="font-mono font-semibold">{{ $keyword }}</span>, чтобы подтвердить
                </label>
                <input type="text" name="confirmation" required autocomplete="off"
                       class="w-full rounded border-gray-300 px-3 py-2 mb-4"
                       placeholder="{{ $keyword }}"
                       data-delete-keyword="{{ $keyword }}"
                       oninput="this.form.querySelector('[data-delete-submit]').disabled =
                                this.value.trim().toUpperCase() !== this.dataset.deleteKeyword">

                <div class="flex justify-end gap-3">
                    <button type="button" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300"
                            onclick="document.getElementById('{{ $modalId }}').classList.add('hidden')">
                        Отмена
                    </button>
                    <button type="submit" data-delete-submit disabled
                            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        {{ $permanent ? 'Удалить навсегда' : 'В корзину' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
