@extends('layouts.app')

@section('title', $faq->title . ' - ICT')

@section('content')
<div class="max-w-4xl mx-auto px-6 py-8">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center space-x-4">
            <a href="{{ route('home') }}"
                class="inline-flex items-center text-slate-600 hover:text-slate-900 transition-colors duration-200">
                <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Назад на главную
            </a>
        </div>

        @if(user_can_manage_users())
        <div class="flex items-center space-x-2">
            <a href="{{ route('homepage-faq.edit', $faq) }}"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors duration-200">
                <svg class="w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
                Редактировать
            </a>
            <a href="{{ route('homepage-faq.index') }}"
                class="inline-flex items-center px-4 py-2 text-slate-600 hover:text-slate-900 transition-colors duration-200">
                Управление FAQ
            </a>
        </div>
        @endif
    </div>

    <!-- FAQ Content -->
    <article class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-8 border-b border-slate-200">
            <div class="flex items-start justify-between mb-4">
                <div class="flex-1">
                    <h1 class="text-3xl font-bold text-slate-900 mb-4 leading-tight">
                        {{ $faq->title }}
                    </h1>

                    @if($faq->excerpt)
                        <p class="text-lg text-slate-600 leading-relaxed">
                            {{ $faq->excerpt }}
                        </p>
                    @endif
                </div>

                @if(!$faq->is_active)
                    <div class="ml-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            <svg class="w-3 h-3 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                <line x1="9" y1="9" x2="15" y2="15"></line>
                            </svg>
                            Неактивен
                        </span>
                    </div>
                @endif
            </div>

            <!-- Meta Information -->
            <div class="flex items-center text-sm text-slate-500 space-x-6">
                <div class="flex items-center">
                    <svg class="w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M12 6v6l4 2"></path>
                    </svg>
                    Создано {{ $faq->created_at->format('d.m.Y в H:i') }}
                </div>

                @if($faq->author)
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        {{ $faq->author->name }}
                    </div>
                @endif

                @if($faq->created_at != $faq->updated_at)
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                        Обновлено {{ $faq->updated_at->format('d.m.Y в H:i') }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Content -->
        <div class="px-6 py-8">
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
            <div class="prose prose-slate max-w-none">
                @if($faq->content)
                    {!! $faq->content !!}
                @elseif($faq->markdown)
                    <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                        <p class="text-sm text-slate-600 mb-2">Содержимое не было обработано:</p>
                        <pre class="text-sm text-slate-800 whitespace-pre-wrap">{{ $faq->markdown }}</pre>
                    </div>
                @else
                    <div class="text-center py-8">
                        <div class="mx-auto w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14,2 14,8 20,8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10,9 9,9 8,9"></polyline>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-slate-900 mb-2">Нет содержимого</h3>
                        <p class="text-slate-600">Содержимое FAQ пока не добавлено</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Footer Actions -->
        @if(user_can_manage_users())
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200">
            <div class="flex items-center justify-between">
                <div class="text-sm text-slate-500">
                    Порядок сортировки: {{ $faq->sort_order }}
                </div>

                <div class="flex items-center space-x-4">
                    <form action="{{ route('homepage-faq.toggle-active', $faq) }}" method="POST" class="inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                                class="inline-flex items-center px-3 py-2 {{ $faq->is_active ? 'bg-orange-100 text-orange-700 hover:bg-orange-200' : 'bg-green-100 text-green-700 hover:bg-green-200' }} text-sm font-medium rounded-lg transition-colors duration-200">
                            @if($faq->is_active)
                                <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="3"></circle>
                                    <path d="M12 1v6m0 6v6m11-7h-6m-6 0H1"></path>
                                </svg>
                                Деактивировать
                            @else
                                <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M9 12l2 2 4-4"></path>
                                    <path d="M21 12c-1 0-2-1-2-2s1-2 2-2 2 1 2 2-1 2-2 2"></path>
                                    <path d="M3 12c1 0 2-1 2-2s-1-2-2-2-2 1-2 2 1 2 2 2"></path>
                                    <path d="M12 3c0 1-1 2-2 2s-2 1-2 2 1 2 2 2 2 1 2 2 1-2 2-2 2-1 2-2-1-2-2-2-2-1-2-2"></path>
                                </svg>
                                Активировать
                            @endif
                        </button>
                    </form>

                    <form action="{{ route('homepage-faq.destroy', $faq) }}" method="POST" class="inline"
                          onsubmit="return confirm('Вы уверены, что хотите удалить этот FAQ?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center px-3 py-2 bg-red-100 text-red-700 hover:bg-red-200 text-sm font-medium rounded-lg transition-colors duration-200">
                            <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3,6 5,6 21,6"></polyline>
                                <path d="M19,6v14a2,2 0 0,1 -2,2H7a2,2 0 0,1 -2,-2V6m3,0V4a2,2 0 0,1 2,-2h4a2,2 0 0,1 2,2v2"></path>
                            </svg>
                            Удалить
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endif
    </article>

    <!-- Back to Home -->
    <div class="mt-8 text-center">
        <a href="{{ route('home') }}"
            class="inline-flex items-center px-6 py-3 bg-slate-900 text-white font-medium rounded-lg hover:bg-slate-800 transition-colors duration-200">
            <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9,22 9,12 15,12 15,22"></polyline>
            </svg>
            Вернуться на главную
        </a>
    </div>
</div>
@endsection
