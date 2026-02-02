@extends('layouts.app')

@section('title', 'Добавление записи об обслуживании - ICT')

@section('content')
<div class="container-width section-padding">
    <!-- Breadcrumbs -->
    <div class="mb-5">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('dashboard') }}" class="text-sm text-slate-500 hover:text-slate-700 inline-flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path></svg>
                        Главная
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-slate-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                        <a href="{{ route('equipment.index') }}" class="ml-1 text-sm text-slate-500 hover:text-slate-700 md:ml-2">Оборудование</a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-slate-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                        <a href="{{ route('equipment.show', $equipment) }}" class="ml-1 text-sm text-slate-500 hover:text-slate-700 md:ml-2">{{ $equipment->inventory_number }}</a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-slate-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                        <a href="{{ route('equipment.service.index', $equipment) }}" class="ml-1 text-sm text-slate-500 hover:text-slate-700 md:ml-2">История обслуживания</a>
                    </div>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-slate-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                        <span class="ml-1 text-sm text-slate-500 md:ml-2">Добавление записи</span>
                    </div>
                </li>
            </ol>
        </nav>
    </div>

    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-900 mb-2">Добавление записи об обслуживании</h1>
        <p class="text-slate-600">
            {{ $equipment->name ?? 'Оборудование' }}
            <span class="font-semibold">({{ $equipment->inventory_number }})</span>
        </p>
    </div>

    <!-- Equipment Info Card -->
    <div class="card p-6 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <h3 class="text-sm font-medium text-slate-500 mb-1">Инвентарный номер</h3>
                <p class="text-lg font-semibold text-slate-900">{{ $equipment->inventory_number }}</p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-slate-500 mb-1">Название</h3>
                <p class="text-lg font-semibold text-slate-900">{{ $equipment->name ?? 'Не указано' }}</p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-slate-500 mb-1">Текущее расположение</h3>
                <p class="text-lg font-semibold text-slate-900">
                    @if($equipment->room)
                        Кабинет {{ $equipment->room->number }} - {{ $equipment->room->name ?? $equipment->room->type_name }}
                    @else
                        Не указано
                    @endif
                </p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-slate-500 mb-1">Категория</h3>
                <p class="text-lg font-semibold text-slate-900">{{ $equipment->category->name ?? 'Не указана' }}</p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-slate-500 mb-1">Статус</h3>
                <p class="text-lg font-semibold text-slate-900">{{ $equipment->status->name ?? 'Не указан' }}</p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-slate-500 mb-1">Последнее обслуживание</h3>
                <p class="text-lg font-semibold text-slate-900">
                    {{ $equipment->last_service_date ? $equipment->last_service_date->format('d.m.Y') : 'Не проводилось' }}
                </p>
            </div>
        </div>
    </div>

    <!-- Form -->
    <div class="card">
        <div class="p-6 border-b border-slate-200">
            <h2 class="text-xl font-semibold text-slate-900">Информация об обслуживании</h2>
        </div>

        <form action="{{ route('equipment.service.store', $equipment) }}" method="POST" enctype="multipart/form-data" class="p-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Дата обслуживания -->
                <div>
                    <label for="service_date" class="form-label">Дата обслуживания <span class="text-red-500">*</span></label>
                    <input type="datetime-local" id="service_date" name="service_date" class="form-input @error('service_date') is-invalid @enderror" value="{{ old('service_date', now()->format('Y-m-d\TH:i')) }}" required>
                    @error('service_date')
                        <div class="text-red-500 mt-2 text-sm">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Тип обслуживания -->
                <div>
                    <label for="service_type" class="form-label">Тип обслуживания <span class="text-red-500">*</span></label>
                    <select id="service_type" name="service_type" class="form-input @error('service_type') is-invalid @enderror" required>
                        <option value="">Выберите тип обслуживания</option>
                        @foreach($serviceTypes as $value => $label)
                            <option value="{{ $value }}" {{ old('service_type') == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('service_type')
                        <div class="text-red-500 mt-2 text-sm">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Описание работ -->
            <div class="mb-6">
                <label for="description" class="form-label">Описание выполненных работ <span class="text-red-500">*</span></label>
                <textarea id="description" name="description" rows="4" class="form-input @error('description') is-invalid @enderror" required>{{ old('description') }}</textarea>
                @error('description')
                    <div class="text-red-500 mt-2 text-sm">{{ $message }}</div>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Результат обслуживания -->
                <div>
                    <label for="service_result" class="form-label">Результат обслуживания <span class="text-red-500">*</span></label>
                    <select id="service_result" name="service_result" class="form-input @error('service_result') is-invalid @enderror" required>
                        <option value="">Выберите результат</option>
                        @foreach($serviceResults as $value => $label)
                            <option value="{{ $value }}" {{ old('service_result') == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('service_result')
                        <div class="text-red-500 mt-2 text-sm">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Дата следующего обслуживания -->
                <div>
                    <label for="next_service_date" class="form-label">Дата следующего обслуживания</label>
                    <input type="date" id="next_service_date" name="next_service_date" class="form-input @error('next_service_date') is-invalid @enderror" value="{{ old('next_service_date') }}">
                    @error('next_service_date')
                        <div class="text-red-500 mt-2 text-sm">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Обнаруженные проблемы -->
            <div class="mb-6">
                <label for="problems_found" class="form-label">Обнаруженные проблемы</label>
                <textarea id="problems_found" name="problems_found" rows="3" class="form-input @error('problems_found') is-invalid @enderror">{{ old('problems_found') }}</textarea>
                @error('problems_found')
                    <div class="text-red-500 mt-2 text-sm">{{ $message }}</div>
                @enderror
            </div>

            <!-- Устраненные проблемы -->
            <div class="mb-6">
                <label for="problems_fixed" class="form-label">Устраненные проблемы</label>
                <textarea id="problems_fixed" name="problems_fixed" rows="3" class="form-input @error('problems_fixed') is-invalid @enderror">{{ old('problems_fixed') }}</textarea>
                @error('problems_fixed')
                    <div class="text-red-500 mt-2 text-sm">{{ $message }}</div>
                @enderror
            </div>

            <!-- Вложения -->
            <div class="mb-6">
                <label for="attachments" class="form-label">Прикрепить файлы</label>
                <div class="flex flex-col items-center justify-center border-2 border-dashed border-slate-300 rounded-lg p-6 bg-slate-50 cursor-pointer" id="dropzone">
                    <div class="flex flex-col items-center">
                        <svg class="w-8 h-8 text-slate-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        <p class="text-sm text-slate-600 mb-1">Перетащите файлы сюда или нажмите для выбора</p>
                        <p class="text-xs text-slate-500">Поддерживаемые форматы: PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, PNG</p>
                    </div>
                    <input type="file" id="attachments" name="attachments[]" multiple class="hidden @error('attachments') is-invalid @enderror">
                </div>
                <div id="selected-files" class="mt-2 space-y-2"></div>
                @error('attachments')
                    <div class="text-red-500 mt-2 text-sm">{{ $message }}</div>
                @enderror
            </div>

            <!-- Кнопки действий -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <a href="{{ route('equipment.service.index', $equipment) }}" class="btn-outline w-full sm:w-auto">Отмена</a>
                <button type="submit" class="btn-primary w-full sm:w-auto">Сохранить запись</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('attachments');
        const selectedFiles = document.getElementById('selected-files');

        // Обработка клика для выбора файла
        dropzone.addEventListener('click', function() {
            fileInput.click();
        });

        // Предотвращение стандартного поведения браузера при перетаскивании
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        // Визуальные эффекты при перетаскивании
        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, unhighlight, false);
        });

        function highlight() {
            dropzone.classList.add('border-blue-500', 'bg-blue-50');
        }

        function unhighlight() {
            dropzone.classList.remove('border-blue-500', 'bg-blue-50');
        }

        // Обработка перетаскивания файлов
        dropzone.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;

            // Создаем новый объект FileList из существующего и нового списка файлов
            const newFileList = new DataTransfer();

            // Добавляем существующие файлы из инпута
            if (fileInput.files.length > 0) {
                for (let i = 0; i < fileInput.files.length; i++) {
                    newFileList.items.add(fileInput.files[i]);
                }
            }

            // Добавляем новые файлы из перетаскивания
            for (let i = 0; i < files.length; i++) {
                newFileList.items.add(files[i]);
            }

            // Устанавливаем новый список файлов
            fileInput.files = newFileList.files;

            // Обновляем отображение списка файлов
            updateFileList();
        }

        // Обработка выбора файлов через инпут
        fileInput.addEventListener('change', updateFileList);

        function updateFileList() {
            selectedFiles.innerHTML = '';

            if (fileInput.files.length > 0) {
                for (let i = 0; i < fileInput.files.length; i++) {
                    const file = fileInput.files[i];
                    const fileSize = formatFileSize(file.size);
                    const fileExtension = file.name.split('.').pop().toLowerCase();

                    const fileItem = document.createElement('div');
                    fileItem.className = 'flex items-center justify-between bg-slate-50 p-2 rounded border border-slate-200';

                    // Определяем иконку в зависимости от типа файла
                    let fileIcon = '';
                    if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExtension)) {
                        fileIcon = '<svg class="w-5 h-5 text-blue-500 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path></svg>';
                    } else if (['pdf'].includes(fileExtension)) {
                        fileIcon = '<svg class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path></svg>';
                    } else if (['doc', 'docx'].includes(fileExtension)) {
                        fileIcon = '<svg class="w-5 h-5 text-blue-700 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path></svg>';
                    } else if (['xls', 'xlsx'].includes(fileExtension)) {
                        fileIcon = '<svg class="w-5 h-5 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path></svg>';
                    } else {
                        fileIcon = '<svg class="w-5 h-5 text-slate-500 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path></svg>';
                    }

                    fileItem.innerHTML = `
                        <div class="flex items-center">
                            ${fileIcon}
                            <div class="flex flex-col">
                                <span class="text-sm font-medium text-slate-700">${file.name}</span>
                                <span class="text-xs text-slate-500">${fileSize}</span>
                            </div>
                        </div>
                        <button type="button" class="text-red-500 hover:text-red-700 remove-file" data-index="${i}">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    `;

                    selectedFiles.appendChild(fileItem);
                }

                // Добавляем обработчики для кнопок удаления
                document.querySelectorAll('.remove-file').forEach(button => {
                    button.addEventListener('click', function() {
                        const index = parseInt(this.dataset.index);
                        removeFile(index);
                    });
                });
            }
        }

        // Функция для удаления файла из списка
        function removeFile(index) {
            const dt = new DataTransfer();

            for (let i = 0; i < fileInput.files.length; i++) {
                if (i !== index) {
                    dt.items.add(fileInput.files[i]);
                }
            }

            fileInput.files = dt.files;
            updateFileList();
        }

        // Форматирование размера файла
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Байт';

            const k = 1024;
            const sizes = ['Байт', 'КБ', 'МБ', 'ГБ'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));

            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    });
</script>
@endpush
