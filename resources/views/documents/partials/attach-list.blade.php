{{--
    Переиспользуемый блок "Документы" для карточки любой сущности.
    Ожидает переменные:
    - $documentable: модель, к которой привязаны документы (Equipment, Purchase, WriteOff, ConsumableWriteOff)
    - $documentTypeSlug: строковый слаг маршрута (equipment, purchase, write-off, consumable-write-off)
    - $documents: коллекция документов ($documentable->documents, желательно уже загруженная через with())
--}}
<div class="card p-6 mb-8">
    <h2 class="text-lg font-semibold text-slate-900 mb-4">Документы</h2>

    @if($documents->isEmpty())
    <p class="text-sm text-gray-500 mb-4">Документов пока нет</p>
    @else
    <ul class="divide-y divide-gray-200 mb-4">
        @foreach($documents as $document)
        <li class="flex items-center justify-between py-3">
            <div class="min-w-0">
                <a href="{{ route('documents.download', $document) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800 truncate block">
                    {{ $document->original_name }}
                </a>
                <span class="text-xs text-gray-400">
                    {{ $document->type_name }} · {{ $document->human_size }} · {{ $document->created_at->format('d.m.Y') }}
                    @if($document->uploadedBy) · {{ $document->uploadedBy->name }} @endif
                </span>
                @if($document->description)
                <div class="text-xs text-gray-500 mt-0.5">{{ $document->description }}</div>
                @endif
            </div>
            @if(auth()->user()->hasRole(['admin', 'master']))
            <form action="{{ route('documents.destroy', $document) }}" method="POST"
                  onsubmit="return confirm('Удалить документ «{{ $document->original_name }}»?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-800 ml-3 whitespace-nowrap">Удалить</button>
            </form>
            @endif
        </li>
        @endforeach
    </ul>
    @endif

    <form action="{{ route('documents.store', [$documentTypeSlug, $documentable->id]) }}" method="POST" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3">
        @csrf
        <div>
            <label class="block text-xs text-gray-500 mb-1">Тип документа</label>
            <select name="type" class="rounded border-gray-300 px-2 py-1.5 text-sm">
                @foreach(\App\Models\Document::typeOptions() as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex-1 min-w-[10rem]">
            <label class="block text-xs text-gray-500 mb-1">Описание</label>
            <input type="text" name="description" maxlength="255" class="rounded border-gray-300 px-2 py-1.5 text-sm w-full">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Файл</label>
            <input type="file" name="file" required class="text-sm">
        </div>
        <button type="submit" class="btn-primary text-sm py-1.5">Прикрепить</button>
    </form>
    <p class="mt-2 text-xs text-gray-500">До 10 МБ. Допустимо: pdf, doc(x), xls(x), ppt(x), txt, csv, zip, изображения.</p>
</div>
