@extends('layouts.app')

@section('title', 'База знаний - ICT')

@section('content')
<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-12">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center">
                    <div class="bg-gradient-to-r from-green-500 to-green-600 w-16 h-16 rounded-full flex items-center justify-center mr-4">
                        <svg class="w-8 h-8 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-4xl font-bold text-gray-900 mb-1">База знаний</h1>
                        <p class="text-xl text-gray-600 max-w-3xl">
                            Найдите ответы на часто задаваемые вопросы и инструкции по решению технических проблем
                        </p>
                    </div>
                </div>

                <div>
                    @php
                    $user = auth()->user();
                    $role = optional($user)->role ? optional($user->role)->slug : null;
                    @endphp

                    @if($user && in_array($role, ['admin','master','technician']))
                    <a href="{{ route('knowledge.create') }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                        Добавить статью
                    </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
            <form action="{{ route('knowledge.index') }}" method="GET" class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <div class="relative">
                        <svg class="absolute left-3 top-3 w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.3-4.3"></path>
                        </svg>
                        <input type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Поиск по статьям..."
                            maxlength="100"
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="4" y1="21" x2="4" y2="14"></line>
                            <line x1="4" y1="10" x2="4" y2="3"></line>
                            <line x1="12" y1="21" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12" y2="3"></line>
                            <line x1="20" y1="21" x2="20" y2="16"></line>
                            <line x1="20" y1="12" x2="20" y2="3"></line>
                            <line x1="1" y1="14" x2="7" y2="14"></line>
                            <line x1="9" y1="8" x2="15" y2="8"></line>
                            <line x1="17" y1="16" x2="23" y2="16"></line>
                        </svg>
                        <select name="category"
                            class="border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent py-2 pl-2 pr-8 text-sm">
                            <option value="">Все категории</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit"
                        class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                        Поиск
                    </button>
                </div>
            </form>
        </div>

        <!-- Articles Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($articles as $article)
            <article class="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-200">
                <a href="{{ route('knowledge.show', $article) }}" class="block p-6">
                    <div class="flex items-center justify-between mb-4">
                        @if($article->category)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium text-white"
                              style="background-color: {{ $article->category->color }}">
                            {{ $article->category->name }}
                        </span>
                        @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            Без категории
                        </span>
                        @endif
                        <div class="flex items-center text-sm text-gray-500">
                            <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            {{ $article->views_count }}
                        </div>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2 line-clamp-2">{{ $article->title }}</h3>
                    <p class="text-gray-600 text-sm line-clamp-3 mb-4">{{ $article->excerpt }}</p>
                        <div class="flex items-center text-sm text-gray-500">
                            <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                            <span>{{ $article->created_at ? $article->created_at->format('d.m.Y') : '—' }}</span>
                        </div>
                </a>
            </article>
            @empty
            <div class="col-span-full text-center py-12">
                <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M16 16s-1.5-2-4-2-4 2-4 2"></path>
                    <line x1="9" y1="9" x2="9.01" y2="9"></line>
                    <line x1="15" y1="9" x2="15.01" y2="9"></line>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-1">Ничего не найдено</h3>
                <p class="text-gray-500">Попробуйте изменить параметры поиска</p>
            </div>
            @endforelse
        </div>

        @if($articles->hasPages())
        <div class="mt-8">
            {{ $articles->links() }}
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('input[name="search"]');
    const categorySelect = document.querySelector('select[name="category"]');
    const form = document.querySelector('form');

    let searchTimeout;

    // Функция для выполнения поиска
    function performSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            // Показать индикатор загрузки
            showLoadingIndicator();

            // Отправить форму
            const formData = new FormData(form);
            const params = new URLSearchParams(formData);

            fetch(window.location.pathname + '?' + params.toString())
                .then(response => response.text())
                .then(html => {
                    // Создать временный элемент для парсинга HTML
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = html;

                    // Найти новый контент в ответе
                    const newArticlesGrid = tempDiv.querySelector('.grid.grid-cols-1.md\\:grid-cols-2.lg\\:grid-cols-3.gap-8');
                    const currentArticlesGrid = document.querySelector('.grid.grid-cols-1.md\\:grid-cols-2.lg\\:grid-cols-3.gap-8');

                    if (newArticlesGrid && currentArticlesGrid) {
                        currentArticlesGrid.innerHTML = newArticlesGrid.innerHTML;
                    }

                    // Обновить пагинацию
                    const newPagination = tempDiv.querySelector('.mt-8');
                    const currentPagination = document.querySelector('.mt-8');

                    if (newPagination && currentPagination) {
                        currentPagination.innerHTML = newPagination.innerHTML;
                    }

                    hideLoadingIndicator();
                })
                .catch(error => {
                    console.error('Ошибка поиска:', error);
                    hideLoadingIndicator();
                });
        }, 300); // Задержка 300ms для избежания избыточных запросов
    }

    function showLoadingIndicator() {
        const grid = document.querySelector('.grid.grid-cols-1.md\\:grid-cols-2.lg\\:grid-cols-3.gap-8');
        if (grid) {
            grid.style.opacity = '0.5';
        }
    }

    function hideLoadingIndicator() {
        const grid = document.querySelector('.grid.grid-cols-1.md\\:grid-cols-2.lg\\:grid-cols-3.gap-8');
        if (grid) {
            grid.style.opacity = '1';
        }
    }

    // Добавить обработчики событий
    if (searchInput) {
        searchInput.addEventListener('input', performSearch);
    }

    if (categorySelect) {
        categorySelect.addEventListener('change', performSearch);
    }

    // Обработчик для кнопки "Поиск" (оставляем для совместимости)
    const submitBtn = document.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.addEventListener('click', function(e) {
            e.preventDefault();
            performSearch();
        });
    }
});
</script>
@endpush
@endsection
