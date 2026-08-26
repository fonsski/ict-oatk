@extends('layouts.app')

@section('title', 'Типы документов - ICT Help')

@section('content')
<div class="max-w-4xl mx-auto px-6 py-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <nav class="text-sm text-slate-500 mb-1">
                <a href="{{ route('documents.index') }}" class="hover:text-slate-700">Документы</a>
                <span class="mx-1">/</span>
                <span>Типы</span>
            </nav>
            <h1 class="text-3xl font-bold text-slate-900">Типы документов</h1>
            <p class="text-slate-600 mt-1">Справочник типов для классификации документов в системе.</p>
        </div>
        <a href="{{ route('documents.index') }}" class="inline-flex items-center px-4 py-2 bg-white text-slate-700 font-medium rounded-lg border border-slate-300 hover:bg-slate-50 transition-colors self-start">
            Назад к документам
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

    <!-- Добавить тип -->
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-5 mb-6">
        <form action="{{ route('document-types.store') }}" method="POST" class="flex flex-col sm:flex-row sm:items-end gap-3">
            @csrf
            <div class="flex-1">
                <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Новый тип</label>
                <input type="text" name="name" id="name" maxlength="100" required value="{{ old('name') }}"
                       placeholder="Например: Спецификация"
                       class="w-full rounded-lg border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">Добавить</button>
        </form>
    </div>

    <!-- Список типов -->
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Название</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Идентификатор</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Документов</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">Действия</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @foreach($types as $type)
                <tr>
                    <td class="px-6 py-4">
                        <form action="{{ route('document-types.update', $type) }}" method="POST" class="flex items-center gap-2">
                            @csrf
                            @method('PUT')
                            <input type="text" name="name" value="{{ $type->name }}" maxlength="100" required
                                   class="rounded-lg border-slate-300 px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <button type="submit" class="text-sm text-blue-600 hover:text-blue-800">Сохранить</button>
                        </form>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-500 font-mono">{{ $type->slug }}</td>
                    <td class="px-6 py-4 text-sm text-slate-700">{{ $usage[$type->slug] ?? 0 }}</td>
                    <td class="px-6 py-4 text-right">
                        @if(($usage[$type->slug] ?? 0) === 0)
                        <form action="{{ route('document-types.destroy', $type) }}" method="POST" class="inline"
                              onsubmit="return confirm('Удалить тип «{{ $type->name }}»?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm text-red-600 hover:text-red-800">Удалить</button>
                        </form>
                        @else
                        <span class="text-xs text-slate-400">используется</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
