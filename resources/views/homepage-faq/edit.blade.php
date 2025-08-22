@extends('layouts.app')

@section('title', 'Редактировать FAQ - ICT')

@section('content')
<div class="max-w-4xl mx-auto px-6 py-8">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 mb-2">Редактировать FAQ</h1>
            <p class="text-slate-600">Изменить существующий вопрос на главной странице</p>
        </div>
        <a href="{{ route('homepage-faq.index') }}"
            class="inline-flex items-center px-4 py-2 text-slate-600 hover:text-slate-900 transition-colors duration-200">
            <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Назад к списку
        </a>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow-sm border border-slate-200">
        <form action="{{ route('homepage-faq.update', $faq) }}" method="POST" class="p-6">
            @csrf
            @method('PUT')

            <!-- Title -->
            <div class="mb-6">
                <label for="title" class="block text-sm font-medium text-slate-700 mb-2">
                    Заголовок <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       id="title"
                       name="title"
                       value="{{ old('title', $faq->title) }}"
                       class="block w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('title') border-red-300 @enderror"
                       placeholder="Введите заголовок FAQ"
                       required>
                @error('title')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Excerpt -->
            <div class="mb-6">
                <label for="excerpt" class="block text-sm font-medium text-slate-700 mb-2">
                    Краткое описание
                </label>
                <textarea id="excerpt"
                          name="excerpt"
                          rows="3"
                          class="block w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('excerpt') border-red-300 @enderror"
                          placeholder="Краткое описание (будет показано в списке)">{{ old('excerpt', $faq->excerpt) }}</textarea>
                @error('excerpt')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-slate-500">Максимум 500 символов. Если не указано, будет создано автоматически из содержимого.</p>
            </div>

            <!-- Content -->
            <div class="mb-6">
                <label for="content" class="block text-sm font-medium text-slate-700 mb-2">
                    Содержимое <span class="text-red-500">*</span>
                </label>
                <div class="border border-slate-300 rounded-md overflow-hidden">
                    <!-- Toolbar -->
                    <div class="border-b border-slate-200 px-3 py-2 bg-slate-50 flex items-center space-x-2">
                        <button type="button" class="text-slate-600 hover:text-slate-900 p-1 rounded"
                                onclick="insertMarkdown('**', '**')" title="Жирный текст">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 4h8a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"></path>
                                <path d="M6 12h9a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"></path>
                            </svg>
                        </button>
                        <button type="button" class="text-slate-600 hover:text-slate-900 p-1 rounded"
                                onclick="insertMarkdown('*', '*')" title="Курсив">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="19" y1="4" x2="10" y2="4"></line>
                                <line x1="14" y1="20" x2="5" y2="20"></line>
                                <line x1="15" y1="4" x2="9" y2="20"></line>
                            </svg>
                        </button>
                        <div class="border-l border-slate-300 h-6"></div>
                        <button type="button" class="text-slate-600 hover:text-slate-900 p-1 rounded"
                                onclick="insertMarkdown('## ', '')" title="Заголовок">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 12h12"></path>
                                <path d="M6 20V4"></path>
                                <path d="M18 20V4"></path>
                            </svg>
                        </button>
                        <button type="button" class="text-slate-600 hover:text-slate-900 p-1 rounded"
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
                        <div class="border-l border-slate-300 h-6"></div>
                        <button type="button"
                                class="text-slate-600 hover:text-slate-900 px-2 py-1 text-xs rounded border border-slate-300 hover:border-slate-400"
                                onclick="togglePreview()">
                            Предпросмотр
                        </button>
                    </div>

                    <!-- Content Textarea -->
                    <textarea id="content"
                              name="content"
                              rows="15"
                              class="block w-full px-3 py-3 border-0 resize-none focus:outline-none focus:ring-0 @error('content') border-red-300 @enderror"
                              placeholder="Введите содержимое FAQ в формате Markdown..."
                              required>{{ old('content', $faq->markdown) }}</textarea>

                    <!-- Preview Area -->
                    <div id="preview-area" class="hidden px-3 py-3 bg-slate-50 border-t border-slate-200">
                        <div class="prose prose-sm max-w-none" id="preview-content">
                            <!-- Preview content will be inserted here -->
                        </div>
                    </div>
                </div>
                @error('content')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-slate-500">Используйте Markdown для форматирования текста.</p>
            </div>

            <!-- Settings -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Active Status -->
                <div>
                    <label class="flex items-center">
                        <input type="checkbox"
                               name="is_active"
                               value="1"
                               {{ old('is_active', $faq->is_active) ? 'checked' : '' }}
                               class="rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500 focus:ring-offset-0">
                        <span class="ml-2 text-sm text-slate-700">Активный (отображается на главной странице)</span>
                    </label>
                </div>

                <!-- Sort Order -->
                <div>
                    <label for="sort_order" class="block text-sm font-medium text-slate-700 mb-1">
                        Порядок сортировки
                    </label>
                    <input type="number"
                           id="sort_order"
                           name="sort_order"
                           value="{{ old('sort_order', $faq->sort_order) }}"
                           min="0"
                           class="block w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('sort_order') border-red-300 @enderror">
                    @error('sort_order')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-slate-500">Чем меньше число, тем выше в списке</p>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end space-x-4 pt-6 border-t border-slate-200">
                <a href="{{ route('homepage-faq.index') }}"
                    class="px-4 py-2 text-slate-700 hover:text-slate-900 transition-colors duration-200">
                    Отмена
                </a>
                <button type="submit"
                        class="inline-flex items-center px-6 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200">
                    <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17,21 17,13 7,13 7,21"></polyline>
                        <polyline points="7,3 7,8 15,8"></polyline>
                    </svg>
                    Сохранить изменения
                </button>
            </div>
        </form>
    </div>
</div>

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
            fetch('{{ route("homepage-faq.preview") }}', {
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
            previewContent.innerHTML = '<p class="text-slate-500">Нет содержимого для предпросмотра</p>';
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
@endsection
