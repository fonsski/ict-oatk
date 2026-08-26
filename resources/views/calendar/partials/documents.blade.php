{{--
    Документы события или задачи.
    Ожидает: $subject (модель с documents()), $type ('event'|'task').
    Загружать может любой, кто видит объект (страница и так закрыта для
    посторонних). Закрытые документы видит только их автор и управляющие.
--}}
@php
    $me = auth()->id();
    $isManager = auth()->user()->hasRole(['admin', 'master']);
    $visibleDocs = $subject->documents->filter(
        fn ($d) => !$d->is_private || $d->uploaded_by_user_id === $me || $isManager,
    );
@endphp

<div class="mt-6 pt-4 border-t border-slate-100">
    <h3 class="text-sm font-medium text-slate-500 mb-3">Документы</h3>

    @if ($visibleDocs->isEmpty())
        <p class="text-sm text-slate-500 mb-3">Пока не прикреплено.</p>
    @else
        <ul class="space-y-2 mb-3">
            @foreach ($visibleDocs as $document)
                <li class="flex items-center gap-2 text-sm">
                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 4H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V18a2 2 0 01-2 2z"/></svg>
                    <a href="{{ route('calendar.documents.download', $document) }}" class="text-blue-600 hover:underline truncate">
                        {{ $document->original_name }}
                    </a>
                    @if ($document->is_private)
                        <span class="shrink-0 inline-flex items-center gap-0.5 text-xs text-slate-500" title="Виден только вам и управляющим">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            закрытый
                        </span>
                    @endif
                    <span class="text-xs text-slate-400 shrink-0">{{ number_format($document->size / 1024, 0) }} КБ</span>
                    @if ($document->uploaded_by_user_id === $me || $isManager)
                        <form method="POST" action="{{ route('calendar.documents.destroy', $document) }}"
                              onsubmit="return confirm('Удалить документ?')" class="shrink-0">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-slate-400 hover:text-red-600" title="Удалить">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </form>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('calendar.documents.store', ['type' => $type, 'id' => $subject->id]) }}"
          enctype="multipart/form-data" class="space-y-2">
        @csrf
        <div class="flex flex-wrap items-center gap-2">
            <input type="file" name="file" required
                   class="text-sm text-slate-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
            <button type="submit" class="btn-outline py-1.5 px-4 text-sm">Прикрепить</button>
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="is_private" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
            Закрытый — виден только мне и управляющим
        </label>
        @error('file')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
        <p class="text-xs text-slate-500">До 10 МБ. PDF, Word, Excel, изображения, архивы.</p>
    </form>
</div>
