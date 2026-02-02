@extends('layouts.app')

@section('title', 'Создать FAQ - ICT')

@section('content')
<div class="max-w-4xl mx-auto px-6 py-8">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 mb-2">Создать FAQ</h1>
            <p class="text-slate-600">Добавить новый вопрос для отображения на главной странице</p>
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
        <form action="{{ route('homepage-faq.store') }}" method="POST" class="p-6">
            @csrf

            <!-- Title -->
            <div class="mb-6">
                <label for="title" class="block text-sm font-medium text-slate-700 mb-2">
                    Заголовок <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       id="title"
                       name="title"
                       value="{{ old('title') }}"
                       maxlength="100"
                       minlength="5"
                       data-char-counter
                       data-max-length="100"
                       data-min-length="5"
                       data-warning-threshold="80"
                       data-help-text="Минимум 5, максимум 100 символов"
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
                          placeholder="Краткое описание (будет показано в списке)">{{ old('excerpt') }}</textarea>
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
                        <button type="button" class="text-slate-600 hover:text-slate-900 p-1 rounded"
                                onclick="openImageUpload()" title="Добавить изображение">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                <polyline points="21 15 16 10 5 21"></polyline>
                            </svg>
                        </button>
                        <input type="file" id="image-upload" name="image" accept="image/*" class="hidden" />
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
                              required>{{ old('content') }}</textarea>

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
                               {{ old('is_active', true) ? 'checked' : '' }}
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
                           value="{{ old('sort_order', 0) }}"
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
                <div class="flex justify-between items-center mt-8">
                    <div id="autosave-status" class="text-sm text-slate-500">
                        <span id="autosave-indicator" class="hidden">
                            <svg class="inline-block w-4 h-4 mr-1 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span id="autosave-message">Черновик сохранен</span>
                        </span>
                    </div>
                    <div class="flex gap-4">
                        <a href="{{ route('homepage-faq.index') }}"
                           class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 text-slate-700 font-medium rounded-lg hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200">
                            Отмена
                        </a>
                        <button type="submit"
                                class="inline-flex items-center px-6 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200">
                            <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17,21 17,13 7,13 7,21"></polyline>
                                <polyline points="7,3 7,8 15,8"></polyline>
                            </svg>
                            Создать FAQ
                        </button>
                    </div>
                </div>
        </form>
    </div>
</div>

<script>
    // Store for autosave
    const AUTOSAVE_KEY = 'faq_draft';
    let autosaveTimer = null;
    const AUTOSAVE_INTERVAL = 30000; // 30 seconds

    // Load draft on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadDraft();
        setupAutosave();

        // Setup event listener for beforeunload
        window.addEventListener('beforeunload', function(e) {
            const contentField = document.getElementById('content');
            const titleField = document.getElementById('title');

            if (contentField.value.trim().length > 0 || titleField.value.trim().length > 0) {
                // Save the draft one last time
                saveDraft();

                // Show a confirmation dialog
                e.preventDefault();
                e.returnValue = 'У вас есть несохраненные изменения. Вы уверены, что хотите покинуть страницу?';
                return e.returnValue;
            }
        });
    });

    function loadDraft() {
        try {
            const savedDraft = localStorage.getItem(AUTOSAVE_KEY);
            if (savedDraft) {
                const draftData = JSON.parse(savedDraft);

                // Check if data is recent (less than 24 hours old)
                const now = new Date();
                const savedTime = new Date(draftData.timestamp);
                const hoursDiff = (now - savedTime) / (1000 * 60 * 60);

                if (hoursDiff < 24) {
                    if (draftData.title) document.getElementById('title').value = draftData.title;
                    if (draftData.excerpt) document.getElementById('excerpt').value = draftData.excerpt;
                    if (draftData.content) document.getElementById('content').value = draftData.content;
                    if (draftData.is_active !== undefined) {
                        const checkbox = document.getElementById('is_active');
                        if (checkbox) checkbox.checked = draftData.is_active;
                    }
                    if (draftData.sort_order) document.getElementById('sort_order').value = draftData.sort_order;

                    // Show notification
                    showAutosaveNotification('Черновик загружен');
                    setTimeout(() => hideAutosaveNotification(), 3000);
                } else {
                    // Clear old draft
                    localStorage.removeItem(AUTOSAVE_KEY);
                }
            }
        } catch (e) {
            console.error('Error loading draft:', e);
            localStorage.removeItem(AUTOSAVE_KEY);
        }
    }

    function setupAutosave() {
        // Attach to input events
        const fields = ['title', 'excerpt', 'content', 'is_active', 'sort_order'];
        fields.forEach(field => {
            const element = document.getElementById(field);
            if (element) {
                element.addEventListener('input', scheduleAutosave);
                element.addEventListener('change', scheduleAutosave);
            }
        });

        // Start the timer
        autosaveTimer = setInterval(saveDraft, AUTOSAVE_INTERVAL);
    }

    function scheduleAutosave() {
        if (autosaveTimer) {
            clearTimeout(autosaveTimer);
        }
        autosaveTimer = setTimeout(saveDraft, 2000); // 2 seconds delay after typing
    }

    function saveDraft() {
        try {
            const draftData = {
                title: document.getElementById('title').value,
                excerpt: document.getElementById('excerpt').value,
                content: document.getElementById('content').value,
                timestamp: new Date().toISOString()
            };

            const isActiveCheckbox = document.getElementById('is_active');
            if (isActiveCheckbox) {
                draftData.is_active = isActiveCheckbox.checked;
            }

            const sortOrder = document.getElementById('sort_order');
            if (sortOrder) {
                draftData.sort_order = sortOrder.value;
            }

            localStorage.setItem(AUTOSAVE_KEY, JSON.stringify(draftData));
            showAutosaveNotification('Черновик сохранен');
            setTimeout(() => hideAutosaveNotification(), 3000);
        } catch (e) {
            console.error('Error saving draft:', e);
        }
    }

    function showAutosaveNotification(message) {
        const indicator = document.getElementById('autosave-indicator');
        const messageEl = document.getElementById('autosave-message');

        if (indicator && messageEl) {
            messageEl.textContent = message;
            indicator.classList.remove('hidden');
        }
    }

    function hideAutosaveNotification() {
        const indicator = document.getElementById('autosave-indicator');
        if (indicator) {
            indicator.classList.add('hidden');
        }
    }

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

function openImageUpload() {
    document.getElementById('image-upload').click();
}

// Handle image upload
document.addEventListener('DOMContentLoaded', function() {
    const imageUpload = document.getElementById('image-upload');

    imageUpload.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const formData = new FormData();
            formData.append('image', this.files[0]);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

            // Show loading indicator
            const textarea = document.getElementById('content');
            const cursorPos = textarea.selectionStart;
            const placeholderText = '![Загрузка изображения...]()';

            // Create loading indicator
            const loadingIndicator = document.createElement('div');
            loadingIndicator.id = 'image-upload-loading';
            loadingIndicator.classList.add('fixed', 'bottom-4', 'right-4', 'bg-blue-600', 'text-white', 'px-4', 'py-2', 'rounded-md', 'flex', 'items-center', 'shadow-lg', 'z-50');
            loadingIndicator.innerHTML = `
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Загрузка изображения...
            `;
            document.body.appendChild(loadingIndicator);

            // Insert placeholder at cursor position
            textarea.value =
                textarea.value.substring(0, cursorPos) +
                placeholderText +
                textarea.value.substring(cursorPos);

            // Upload the image
            fetch('{{ route("homepage-faq.upload-image") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Ошибка сервера: ' + response.status);
                }
                // Check if response is JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        throw new Error('Сервер вернул не JSON: ' + (text.substring(0, 100) + '...'));
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Replace placeholder with actual markdown image
                    textarea.value = textarea.value.replace(
                        placeholderText,
                        data.markdown
                    );
                } else {
                    // Replace placeholder with error message
                    textarea.value = textarea.value.replace(
                        placeholderText,
                        '<!-- Ошибка загрузки изображения: ' + data.message + ' -->'
                    );
                    console.error('Image upload failed:', data.message);
                    alert('Ошибка загрузки изображения: ' + data.message);
                }
            })
            .catch(error => {
                // Replace placeholder with error message
                textarea.value = textarea.value.replace(
                    placeholderText,
                    '<!-- Ошибка загрузки изображения: ' + (error.message || 'Неизвестная ошибка') + ' -->'
                );
                console.error('Image upload error:', error);
                alert('Ошибка загрузки изображения: ' + (error.message || 'Неизвестная ошибка'));
            })
            .finally(() => {
                // Reset file input
                this.value = '';
                // Remove loading indicator
                const loadingIndicator = document.getElementById('image-upload-loading');
                if (loadingIndicator) {
                    loadingIndicator.remove();
                }
            });
        }
    });
    // Clear draft when form is submitted
    document.querySelector('form').addEventListener('submit', function() {
        localStorage.removeItem(AUTOSAVE_KEY);
    });
});
</script>
@endsection
