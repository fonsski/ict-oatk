@extends('layouts.app')

@section('title', 'Мои черновики - База знаний - ICT')

@section('content')
<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-1">Мои черновики</h1>
                <p class="text-gray-600">Неопубликованные статьи, которые видите только вы</p>
            </div>
            <a href="{{ route('knowledge.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50">
                К базе знаний
            </a>
        </div>

        @if(session('success'))
        <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6">
            <p class="text-sm text-green-700">{{ session('success') }}</p>
        </div>
        @endif

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            @if($articles->count())
            <ul class="divide-y divide-gray-200">
                @foreach($articles as $article)
                <li class="p-6 hover:bg-gray-50 flex items-center justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">Черновик</span>
                            <span class="text-xs text-gray-500">{{ optional($article->category)->name ?? 'Без категории' }}</span>
                        </div>
                        <a href="{{ route('knowledge.show', $article) }}" class="text-lg font-medium text-gray-900 hover:text-blue-600 truncate block">
                            {{ $article->title }}
                        </a>
                        <p class="text-sm text-gray-500">Изменён: {{ format_datetime($article->updated_at) }}</p>
                    </div>
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <a href="{{ route('knowledge.edit', $article) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">
                            Продолжить
                        </a>
                        <form action="{{ route('knowledge.publish', $article) }}" method="POST">
                            @csrf
                            <button type="submit" class="text-sm font-medium text-green-700 hover:text-green-900">Опубликовать</button>
                        </form>
                    </div>
                </li>
                @endforeach
            </ul>
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $articles->links() }}
            </div>
            @else
            <div class="text-center py-16">
                <h3 class="text-lg font-medium text-gray-900 mb-2">Черновиков нет</h3>
                <p class="text-gray-600">Начните писать статью и сохраните её как черновик</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
