@extends('layouts.app')

@section('title', 'Корзина базы знаний - ICT')

@section('content')
<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-1">Корзина базы знаний</h1>
                <p class="text-gray-600">Удалённые статьи можно восстановить или удалить безвозвратно</p>
            </div>
            <a href="{{ route('knowledge.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50">
                К списку статей
            </a>
        </div>

        @if(session('success'))
        <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6">
            <p class="text-sm text-green-700">{{ session('success') }}</p>
        </div>
        @endif

        @if(session('error'))
        <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
            <p class="text-sm text-red-700">{{ session('error') }}</p>
        </div>
        @endif

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            @if($articles->count())
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статья</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Категория</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Удалена</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Действия</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($articles as $article)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $article->title }}</div>
                            <div class="text-sm text-gray-500">{{ optional($article->author)->name ?? '—' }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ optional($article->category)->name ?? '—' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap">
                            {{ format_datetime($article->deleted_at) }}
                        </td>
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            <form method="POST" action="{{ route('knowledge.restore', $article->slug) }}" class="inline">
                                @csrf
                                <button type="submit" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                    Восстановить
                                </button>
                            </form>

                            @if(user_is_admin())
                            <form method="POST" action="{{ route('knowledge.force-delete', $article->slug) }}" class="inline ml-4"
                                  onsubmit="return confirm('Статья «{{ $article->title }}» будет удалена безвозвратно вместе с изображениями. Продолжить?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">
                                    Удалить навсегда
                                </button>
                            </form>
                            @endif
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
                <h3 class="text-lg font-medium text-gray-900 mb-2">Корзина пуста</h3>
                <p class="text-gray-600">Удалённые статьи будут появляться здесь</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
