@extends('layouts.app')

@section('title', 'Архив базы знаний - ICT')

@section('content')
<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-1">Архив базы знаний</h1>
                <p class="text-gray-600">Статьи, снятые с публикации. Их можно вернуть в базу знаний</p>
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
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статья</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Категория</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Автор</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Действия</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($articles as $article)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <a href="{{ route('knowledge.show', $article) }}" class="text-sm font-medium text-gray-900 hover:text-blue-600">
                                {{ $article->title }}
                            </a>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ optional($article->category)->name ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ optional($article->author)->name ?? '—' }}</td>
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            <form action="{{ route('knowledge.publish', $article) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Вернуть в базу</button>
                            </form>
                            <form action="{{ route('knowledge.destroy', $article) }}" method="POST" class="inline ml-4"
                                  onsubmit="return confirm('Статья будет перемещена в корзину. Продолжить?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">В корзину</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $articles->links() }}
            </div>
            @else
            <div class="text-center py-16">
                <h3 class="text-lg font-medium text-gray-900 mb-2">Архив пуст</h3>
                <p class="text-gray-600">Архивированные статьи будут появляться здесь</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
