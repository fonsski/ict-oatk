@extends('layouts.app')

@section('title', 'Документы - ICT Help')

@section('content')
<div class="max-w-7xl mx-auto px-6 py-8">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Документы</h1>
            <p class="text-slate-600 mt-1">Библиотека документов системы: договоры, счета, акты и заявки.</p>
        </div>
        <a href="{{ route('document-types.index') }}" class="inline-flex items-center px-4 py-2 bg-white text-slate-700 font-medium rounded-lg border border-slate-300 hover:bg-slate-50 transition-colors self-start">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.99 1.99 0 013 12V7a4 4 0 014-4z"></path></svg>
            Типы документов
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">{{ session('error') }}</div>
    @endif
    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
        <ul class="list-disc list-inside text-sm">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <!-- Загрузка документа -->
    <details class="bg-white rounded-lg shadow-sm border border-slate-200 mb-6" {{ $errors->any() ? 'open' : '' }}>
        <summary class="cursor-pointer px-6 py-4 font-medium text-slate-800 select-none flex items-center gap-2">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Загрузить документ
        </summary>
        <form action="{{ route('documents.store-general') }}" method="POST" enctype="multipart/form-data" class="px-6 pb-6 border-t border-slate-100 pt-5 grid grid-cols-1 md:grid-cols-2 gap-5">
            @csrf
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1">Файл <span class="text-red-500">*</span></label>
                <input type="file" name="file" required
                       class="block w-full text-sm text-slate-700 border border-slate-300 rounded-lg px-3 py-2 file:mr-3 file:py-1 file:px-3 file:rounded-md file:border-0 file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                <p class="text-xs text-slate-400 mt-1">До {{ \App\Models\Document::MAX_SIZE_KB / 1024 }} МБ. Форматы: {{ \App\Models\Document::ALLOWED_MIMES }}.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Тип</label>
                <select name="type" class="w-full rounded-lg border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @foreach(\App\Models\Document::typeOptions() as $value => $label)
                    <option value="{{ $value }}" {{ old('type', \App\Models\Document::TYPE_OTHER) == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Описание</label>
                <input type="text" name="description" value="{{ old('description') }}" maxlength="255"
                       class="w-full rounded-lg border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-2">Доступ</label>
                <div class="flex flex-col sm:flex-row gap-4">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="radio" name="is_private" value="0" {{ old('is_private', '0') === '1' ? '' : 'checked' }} class="text-blue-600 focus:ring-blue-500">
                        Общий <span class="text-xs text-slate-400">— виден всем сотрудникам</span>
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="radio" name="is_private" value="1" {{ old('is_private') === '1' ? 'checked' : '' }} class="text-blue-600 focus:ring-blue-500">
                        Приватный <span class="text-xs text-slate-400">— только вам и управляющим</span>
                    </label>
                </div>
            </div>
            <div class="md:col-span-2">
                <button type="submit" class="inline-flex items-center px-5 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">Загрузить</button>
            </div>
        </form>
    </details>

    <!-- Фильтры + таблица -->
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 border-b border-slate-200">
            <form method="GET" class="flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-xs text-slate-500 mb-1">Поиск по имени файла</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="rounded-lg border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                </div>
                <div>
                    <label class="block text-xs text-slate-500 mb-1">Тип</label>
                    <select name="type" class="rounded-lg border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Все типы</option>
                        @foreach(\App\Models\Document::typeOptions() as $value => $label)
                        <option value="{{ $value }}" {{ request('type') == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-slate-500 mb-1">С даты</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="rounded-lg border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                </div>
                <div>
                    <label class="block text-xs text-slate-500 mb-1">По дату</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="rounded-lg border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                </div>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-slate-800 text-white font-medium rounded-lg hover:bg-slate-900 transition-colors">Применить</button>
                <a href="{{ route('documents.index') }}" class="text-sm text-slate-500 hover:text-slate-700 underline">Сбросить</a>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Файл</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Тип</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Привязан к</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Загрузил</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Дата</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Действия</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($documents as $document)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-slate-900">{{ $document->original_name }}</span>
                                @if($document->is_private)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">Приватный</span>
                                @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">Общий</span>
                                @endif
                            </div>
                            <div class="text-xs text-slate-400">{{ $document->human_size }}</div>
                            @if($document->description)
                            <div class="text-xs text-slate-500">{{ $document->description }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700">{{ $document->type_name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700">
                            @if($document->subject_url)
                                <a href="{{ $document->subject_url }}" class="text-blue-600 hover:text-blue-800">{{ $document->subject_label }}</a>
                            @else
                                {{ $document->subject_label }}
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700">{{ $document->uploadedBy->name ?? '—' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700">{{ $document->created_at->format('d.m.Y H:i') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @if($document->is_previewable)
                            <a href="{{ route('documents.preview', $document) }}" class="text-blue-600 hover:text-blue-800 mr-3">Просмотр</a>
                            @endif
                            <a href="{{ route('documents.download', $document) }}" class="text-blue-600 hover:text-blue-800">Скачать</a>
                            @if(auth()->user()->hasRole(['admin', 'master']))
                            <form action="{{ route('documents.destroy', $document) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Удалить документ «{{ $document->original_name }}»?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 ml-3">Удалить</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-slate-500">Документы не найдены</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-200">
            {{ $documents->links() }}
        </div>
    </div>
</div>
@endsection
