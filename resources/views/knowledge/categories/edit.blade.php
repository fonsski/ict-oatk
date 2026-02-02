@extends('layouts.app')

@section('title', 'Редактировать категорию - База знаний - ICT')

@section('content')
<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <a href="{{ route('knowledge.categories.index') }}" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700">
                <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m15 18-6-6 6-6"></path>
                </svg>
                Вернуться к категориям
            </a>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-4">Редактировать категорию</h1>
                <p class="text-gray-600">
                    Измените настройки категории "{{ $category->name }}"
                </p>
            </div>

            <form action="{{ route('knowledge.categories.update', $category) }}" method="POST" class="space-y-6">
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

                <!-- Название категории -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Название категории <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           id="name"
                           name="name"
                           required
                           value="{{ old('name', $category->name) }}"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-300 @enderror"
                           placeholder="Например: Оборудование">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Описание -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        Описание
                    </label>
                    <textarea id="description"
                              name="description"
                              rows="3"
                              class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('description') border-red-300 @enderror"
                              placeholder="Краткое описание категории...">{{ old('description', $category->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500">Максимум 500 символов</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Цвет категории -->
                    <div>
                        <label for="color" class="block text-sm font-medium text-gray-700 mb-2">
                            Цвет категории <span class="text-red-500">*</span>
                        </label>
                        <div class="flex items-center space-x-3">
                            <input type="color"
                                   id="color"
                                   name="color"
                                   required
                                   value="{{ old('color', $category->color) }}"
                                   class="h-10 w-16 border border-gray-300 rounded-md cursor-pointer @error('color') border-red-300 @enderror">
                            <input type="text"
                                   id="color-text"
                                   value="{{ old('color', $category->color) }}"
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="#6B7280">
                        </div>
                        @error('color')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-gray-500">Цвет будет использоваться для отображения категории</p>
                    </div>

                    <!-- Порядок сортировки -->
                    <div>
                        <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-2">
                            Порядок сортировки
                        </label>
                        <input type="number"
                               id="sort_order"
                               name="sort_order"
                               value="{{ old('sort_order', $category->sort_order) }}"
                               min="0"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('sort_order') border-red-300 @enderror">
                        @error('sort_order')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-gray-500">Чем меньше число, тем выше в списке</p>
                    </div>
                </div>

                <!-- Иконка (опционально) -->
                <div>
                    <label for="icon" class="block text-sm font-medium text-gray-700 mb-2">
                        Иконка
                    </label>
                    <input type="text"
                           id="icon"
                           name="icon"
                           value="{{ old('icon', $category->icon) }}"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('icon') border-red-300 @enderror"
                           placeholder="computer-desktop">
                    @error('icon')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500">
                        Имя иконки из Heroicons (опционально).
                        <a href="https://heroicons.com" target="_blank" class="text-blue-600 hover:text-blue-800">Посмотреть доступные иконки</a>
                    </p>
                </div>

                <!-- Статус активности -->
                <div>
                    <div class="flex items-center">
                        <input type="checkbox"
                               id="is_active"
                               name="is_active"
                               value="1"
                               {{ old('is_active', $category->is_active) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 focus:ring-offset-0">
                        <label for="is_active" class="ml-2 block text-sm text-gray-700">
                            Активная категория
                        </label>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">Неактивные категории не отображаются при создании статей</p>
                </div>

                <!-- Статистика категории -->
                @if($category->knowledgeBase)
                <div class="bg-blue-50 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-blue-800 mb-2">Статистика категории:</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <div class="text-2xl font-bold text-blue-900">{{ $category->knowledgeBase->count() }}</div>
                            <div class="text-sm text-blue-700">{{ trans_choice('статья|статьи|статей', $category->knowledgeBase->count()) }}</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-blue-900">{{ $category->knowledgeBase->sum('views_count') }}</div>
                            <div class="text-sm text-blue-700">просмотров</div>
                        </div>
                    </div>
                    @if($category->knowledgeBase->count() > 0)
                        <p class="mt-2 text-sm text-blue-700">
                            <strong>Внимание:</strong> При изменении названия или цвета категории изменения отразятся во всех связанных статьях.
                        </p>
                    @endif
                </div>
                @endif

                <!-- Предпросмотр -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-gray-700 mb-3">Предпросмотр категории:</h3>
                    <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium text-white"
                         id="category-preview" style="background-color: {{ $category->color }}">
                        <span id="preview-name">{{ $category->name }}</span>
                    </div>
                </div>

                <!-- Кнопки действий -->
                <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                    <a href="{{ route('knowledge.categories.index') }}"
                       class="px-4 py-2 text-gray-700 hover:text-gray-900 transition-colors duration-200">
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
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.getElementById('name');
    const colorInput = document.getElementById('color');
    const colorTextInput = document.getElementById('color-text');
    const previewElement = document.getElementById('category-preview');
    const previewName = document.getElementById('preview-name');

    function updatePreview() {
        const name = nameInput.value || '{{ $category->name }}';
        const color = colorInput.value;

        previewName.textContent = name;
        previewElement.style.backgroundColor = color;
    }

    // Синхронизация color input и text input
    colorInput.addEventListener('input', function() {
        colorTextInput.value = this.value;
        updatePreview();
    });

    colorTextInput.addEventListener('input', function() {
        if (/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(this.value)) {
            colorInput.value = this.value;
            updatePreview();
        }
    });

    nameInput.addEventListener('input', updatePreview);

    // Инициализация предпросмотра
    updatePreview();
});
</script>
@endsection
