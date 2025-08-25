@extends('layouts.app')

@section('title', $article->title . ' - База знаний - ICT')

@section('content')
<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <!-- Back Link -->
        <div class="mb-8">
            <a href="{{ route('knowledge.index') }}" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700">
                <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m15 18-6-6 6-6"></path>
                </svg>
                Вернуться к базе знаний
            </a>
        </div>

        <!-- Admin Actions -->
        @php
        $user = auth()->user();
        $role = optional($user)->role ? optional($user->role)->slug : null;
        @endphp

        @if($user && in_array($role, ['admin','master','technician']))
        <div class="flex items-center space-x-2">
            <a href="{{ route('knowledge.edit', $article) }}"
               class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="mr-2 -ml-1 w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
                Редактировать
            </a>
            <form action="{{ route('knowledge.destroy', $article) }}" method="POST" onsubmit="return confirm('Вы уверены, что хотите удалить эту статью?')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <svg class="mr-2 -ml-1 w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                    Удалить
                </button>
            </form>
        </div>
        @endif

        <!-- Article Content -->
        <article class="bg-white rounded-lg shadow-sm border border-gray-200">
            <!-- Article Header -->
            <div class="p-8 border-b border-gray-200">
                <div class="flex flex-wrap items-center gap-4 mb-6">
                    @if($article->category)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium text-white"
                          style="background-color: {{ $article->category->color }}">
                        {{ $article->category->name }}
                    </span>
                    @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                        Без категории
                    </span>
                    @endif

                    <div class="flex items-center text-sm text-gray-500">
                        <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        {{ $article->created_at ? $article->created_at->format('d.m.Y H:i') : '—' }}
                    </div>
                </div>

                <h1 class="text-3xl font-bold text-gray-900 mb-6">{{ $article->title }}</h1>

                @if($article->excerpt)
                <p class="text-lg text-gray-600 mb-6">{{ $article->excerpt }}</p>
                @endif
            </div>

            <!-- Article Body -->
            <div class="p-8">
                <style>
                    .prose img {
                        max-width: 100%;
                        height: auto;
                        border-radius: 0.375rem;
                        margin: 1.5rem 0;
                    }
                    @media (max-width: 640px) {
                        .prose img {
                            width: 100%;
                        }
                    }
                </style>
                <div class="prose max-w-none">
                    {!! $article->content !!}
                </div>

                <!-- Article Footer -->
                <div class="mt-8 pt-6 border-t border-gray-200 flex justify-between items-center">
                    <div class="text-sm text-gray-500">
                        <span>Просмотров: {{ $article->views_count }}</span>
                        @if($article->updated_at && $article->updated_at != $article->created_at)
                        <span class="ml-4">Обновлено: {{ $article->updated_at->format('d.m.Y H:i') }}</span>
                        @endif
                    </div>
            </div>
        </article>

        <!-- Related Articles -->
        @if($relatedArticles->isNotEmpty())
        <div class="mt-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Похожие статьи</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($relatedArticles as $relatedArticle)
                <a href="{{ route('knowledge.show', $relatedArticle) }}"
                    class="block bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-center justify-between mb-4">
                        @if($relatedArticle->category)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium text-white"
                              style="background-color: {{ $relatedArticle->category->color }}">
                            {{ $relatedArticle->category->name }}
                        </span>
                        @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            Без категории
                        </span>
                        @endif
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2 line-clamp-2">
                        {{ $relatedArticle->title }}
                    </h3>
                    @if($relatedArticle->excerpt)
                    <p class="text-gray-600 text-sm line-clamp-2">{{ $relatedArticle->excerpt }}</p>
                    @endif
                </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    async function share() {
        const title = '{{ $article->title }}';
        const url = window.location.href;

        if (navigator.share) {
            try {
                await navigator.share({
                    title: title,
                    url: url
                });
            } catch (err) {
                // Fall back to clipboard
                copyToClipboard();
            }
        } else {
            copyToClipboard();
        }
    }

    function copyToClipboard() {
        navigator.clipboard.writeText(window.location.href).then(function() {
            alert('Ссылка скопирована в буфер обмена');
        });
    }
</script>
@endpush
@endsection
