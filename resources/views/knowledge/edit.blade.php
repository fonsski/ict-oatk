@extends('layouts.app')

@section('title', 'Редактировать статью - ICT')

@section('content')
<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto">
        <div class="mb-8">
            <a href="{{ route('knowledge.index') }}" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700">
                <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m15 18-6-6 6-6"></path>
                </svg>
                Вернуться к базе знаний
            </a>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-4">Редактировать статью</h1>
                <p class="text-gray-600">
                    Измените содержимое статьи
                </p>
            </div>

            <form action="{{ route('knowledge.update', $article) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                @if ($errors->any())
                    <div class="bg-red-50 border-l-4 border-red-500 p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Ошибки при заполнении формы:</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <ul class="list-disc pl-5 space-y-1">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Название статьи -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                        Название статьи
                    </label>
                    <input type="text"
                           id="title"
                           name="title"
                           required
                           value="{{ old('title', $article->title) }}"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <!-- Категория -->
                <div>
                    <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">
                        Категория
                    </label>
                    <select id="category_id"
                            name="category_id"
                            required
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Выберите категорию</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id', $article->category_id) == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Краткое описание -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                        Краткое описание
                    </label>
                    <textarea id="description"
                              name="description"
                              rows="3"
                              required
                              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                              placeholder="Краткое описание статьи...">{{ old('description', $article->excerpt) }}</textarea>
                </div>

                <!-- Содержимое -->
                <div>
                    <label for="content" class="block text-sm font-medium text-gray-700 mb-2">
                        Содержимое статьи <span class="text-red-500">*</span>
                    </label>
                    <div class="border border-gray-300 rounded-md overflow-hidden">
                        <!-- Toolbar -->
                        <div class="border-b border-gray-200 px-3 py-2 bg-gray-50 flex items-center space-x-2">
                            <button type="button" class="text-gray-600 hover:text-gray-900 p-1 rounded"
                                    onclick="insertMarkdown('**', '**')" title="Жирный текст">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M6 4h8a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"></path>
                                    <path d="M6 12h9a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"></path>
                                </svg>
                            </button>
                            <button type="button" class="text-gray-600 hover:text-gray-900 p-1 rounded"
                                    onclick="insertMarkdown('*', '*')" title="Курсив">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="19" y1="4" x2="10" y2="4"></line>
                                    <line x1="14" y1="20" x2="5" y2="20"></line>
                                    <line x1="15" y1="4" x2="9" y2="20"></line>
                                </svg>
                            </button>
                            <div class="border-l border-gray-300 h-6"></div>
                            <button type="button" class="text-gray-600 hover:text-gray-900 p-1 rounded"
                                    onclick="insertMarkdown('## ', '')" title="Заголовок">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M6 12h12"></path>
                                    <path d="M6 20V4"></path>
                                    <path d="M18 20V4"></path>
                                </svg>
                            </button>
                            <button type="button" class="text-gray-600 hover:text-gray-900 p-1 rounded"
                                    onclick="insertMarkdown('- ', '')" title="Список">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="8" y1="6" x2="21" y2="6"></line>
                                    <line x1="8" y1="12" x2="21" y2="12"></line>
                                    <line x1="8" y1="18" x2="21" y2="18"></line>
                                    <line x1="3" y1="6" x2="3.01" y2="6"></line>
                                    <line x1="3" y1="12" x2="3.01" y2="12"></line>
                                    <line x1="3" y1="18" x2="3.01" y2="18"></line>
                                </svg>
                            </button>
                            <div class="border-l border-gray-300 h-6"></div>
                            <button type="button"
                                    class="text-gray-600 hover:text-gray-900 px-2 py-1 text-xs rounded border border-gray-300 hover:border-gray-400"
                                    onclick="togglePreview()">
                                Предпросмотр
                            </button>
                        </div>

                        <!-- Content Textarea -->
                        <textarea id="content"
                                  name="content"
                                  rows="15"
                                  class="block w-full px-3 py-3 border-0 resize-none focus:outline-none focus:ring-0"
                                  placeholder="Введите содержимое статьи в формате Markdown..."
                                  required>{{ old('content', $article->markdown ?? $article->content) }}</textarea>

                        <!-- Preview Area -->
                        <div id="preview-area" class="hidden px-3 py-3 bg-gray-50 border-t border-gray-200">
                            <div class="prose prose-sm max-w-none" id="preview-content">
                                {!! $article->content !!}
                            </div>
                        </div>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">Используйте Markdown для форматирования текста.</p>
                </div>

                <!-- Тэги -->
                <div>
                    <label for="tags" class="block text-sm font-medium text-gray-700 mb-1">
                        Тэги
                    </label>
                    <input type="text"
                           id="tags"
                           name="tags"
                           value="{{ old('tags', $article->tags) }}"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                           placeholder="Введите тэги через запятую...">
                    <p class="mt-1 text-sm text-gray-500">
                        Разделяйте тэги запятыми, например: настройка, windows, принтер
                    </p>
                </div>

                <!-- Кнопки -->
                <div class="pt-4 flex justify-end space-x-4">
                    <a href="{{ route('knowledge.index') }}"
                       class="inline-flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Отмена
                    </a>
                    <button type="submit"
                            class="inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Обновить статью
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

<script>
function insertMarkdown(before, after) {
    const textarea = document.getElementById('content');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selectedText = textarea.value.substring(start, end);

    const newText = before + selectedText + after;
    textarea.value = textarea.value.substring(0, start) + newText + textarea.value.substring(end);

    // Set cursor position
    const newCursorPos = start + before.length + selectedText.length + after.length;
    textarea.focus();
    textarea.setSelectionRange(newCursorPos, newCursorPos);
}

function togglePreview() {
    const previewArea = document.getElementById('preview-area');
    const textarea = document.getElementById('content');
    const previewContent = document.getElementById('preview-content');

    if (previewArea.classList.contains('hidden')) {
        // Show preview
        const content = textarea.value;
        if (content.trim()) {
            // Make AJAX request for preview
            fetch('{{ route("knowledge.preview") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ content: content })
            })
            .then(response => response.json())
            .then(data => {
                previewContent.innerHTML = data.html;
            })
            .catch(error => {
                previewContent.innerHTML = '<p class="text-red-600">Ошибка загрузки предпросмотра</p>';
            });
        } else {
            previewContent.innerHTML = '<p class="text-gray-500">Нет содержимого для предпросмотра</p>';
        }

        previewArea.classList.remove('hidden');
        textarea.style.display = 'none';
    } else {
        // Hide preview
        previewArea.classList.add('hidden');
        textarea.style.display = 'block';
    }
}
</script>
