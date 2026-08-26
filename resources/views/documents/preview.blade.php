@extends('layouts.app')

@section('title', $document->original_name . ' - Просмотр - ICT Help')

@section('content')
<div class="max-w-5xl mx-auto px-6 py-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="min-w-0">
            <a href="{{ route('documents.index') }}" class="text-sm text-slate-500 hover:text-slate-700">← Все документы</a>
            <h1 class="text-2xl font-bold text-slate-900 mt-1 break-words">{{ $document->original_name }}</h1>
            <p class="text-sm text-slate-500 mt-1">
                {{ $document->type_name }} · {{ $document->human_size }}
                @if($document->is_private)
                    · <span class="text-amber-700">Приватный</span>
                @endif
            </p>
        </div>
        <a href="{{ route('documents.download', $document) }}" class="inline-flex items-center px-5 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors whitespace-nowrap self-start">Скачать</a>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
        @switch($document->preview_kind)
            @case('pdf')
                <iframe src="{{ route('documents.raw', $document) }}" class="w-full" style="height: 80vh;" title="{{ $document->original_name }}"></iframe>
                @break

            @case('image')
                <div class="p-4 flex justify-center bg-slate-50">
                    <img src="{{ route('documents.raw', $document) }}" alt="{{ $document->original_name }}" class="max-w-full h-auto rounded" />
                </div>
                @break

            @case('markdown')
                <div class="prose max-w-none p-6" style="overflow-wrap:break-word;word-break:break-word;">
                    {!! (new \Parsedown())->setSafeMode(true)->text($content ?? '') !!}
                </div>
                @break

            @case('text')
                <pre class="p-6 text-sm text-gray-800 whitespace-pre-wrap break-words font-mono">{{ $content }}</pre>
                @break

            @default
                <div class="p-10 text-center text-gray-500">
                    <p class="mb-4">Просмотр этого формата пока недоступен внутри системы.</p>
                    <a href="{{ route('documents.download', $document) }}" class="btn-primary">Скачать файл</a>
                </div>
        @endswitch
    </div>
</div>
@endsection
