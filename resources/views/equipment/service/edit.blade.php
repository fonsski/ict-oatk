@extends('layouts.app')

@section('title', 'Редактирование записи об обслуживании - ICT Help')

@section('content')
<div class="container-width section-padding">
    <!-- Breadcrumbs -->
    <div class="mb-5">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('home') }}" class="text-sm text-slate-500 hover:text-slate-700 inline-flex items-center">
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
                        <span class="ml-1 text-sm text-slate-500 md:ml-2">Редактирование записи</span>
                    </div>
                </li>
            </ol>
        </nav>
    </div>

    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-900 mb-2">Редактирование записи об обслуживании</h1>
        <p class="text-slate-600">
            {{ $equipment->name ?? 'Оборудование' }}
            <span class="font-semibold">({{ $equipment->inventory_number }})</span>
        </p>
    </div>

    <!-- Form -->
    <div class="card">
        <div class="p-6 border-b border-slate-200">
            <h2 class="text-xl font-semibold text-slate-900">Информация об обслуживании</h2>
        </div>

        <form action="{{ route('equipment.service.update', [$equipment, $service]) }}" method="POST" enctype="multipart/form-data" class="p-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Дата обслуживания -->
                <div>
                    <label for="service_date" class="form-label">Дата обслуживания <span class="text-red-500">*</span></label>
                    <input type="datetime-local" id="service_date" name="service_date" class="form-input @error('service_date') is-invalid @enderror" value="{{ old('service_date', optional($service->service_date)->format('Y-m-d\TH:i')) }}" required>
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
                            <option value="{{ $value }}" {{ old('service_type', $service->service_type) == $value ? 'selected' : '' }}>{{ $label }}</option>
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
                <textarea id="description" name="description" rows="4" class="form-input @error('description') is-invalid @enderror" required>{{ old('description', $service->description) }}</textarea>
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
                            <option value="{{ $value }}" {{ old('service_result', $service->service_result) == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('service_result')
                        <div class="text-red-500 mt-2 text-sm">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Дата следующего обслуживания -->
                <div>
                    <label for="next_service_date" class="form-label">Дата следующего обслуживания</label>
                    <input type="date" id="next_service_date" name="next_service_date" class="form-input @error('next_service_date') is-invalid @enderror" value="{{ old('next_service_date', optional($service->next_service_date)->format('Y-m-d')) }}">
                    @error('next_service_date')
                        <div class="text-red-500 mt-2 text-sm">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Обнаруженные проблемы -->
            <div class="mb-6">
                <label for="problems_found" class="form-label">Обнаруженные проблемы</label>
                <textarea id="problems_found" name="problems_found" rows="3" class="form-input @error('problems_found') is-invalid @enderror">{{ old('problems_found', $service->problems_found) }}</textarea>
                @error('problems_found')
                    <div class="text-red-500 mt-2 text-sm">{{ $message }}</div>
                @enderror
            </div>

            <!-- Устраненные проблемы -->
            <div class="mb-6">
                <label for="problems_fixed" class="form-label">Устраненные проблемы</label>
                <textarea id="problems_fixed" name="problems_fixed" rows="3" class="form-input @error('problems_fixed') is-invalid @enderror">{{ old('problems_fixed', $service->problems_fixed) }}</textarea>
                @error('problems_fixed')
                    <div class="text-red-500 mt-2 text-sm">{{ $message }}</div>
                @enderror
            </div>

            <!-- Существующие вложения -->
            @if(!empty($service->attachments) && count($service->attachments) > 0)
            <div class="mb-6">
                <label class="form-label">Прикреплённые файлы</label>
                <p class="text-xs text-slate-500 mb-2">Отметьте файлы, которые нужно удалить при сохранении.</p>
                <div class="space-y-2">
                    @foreach($service->attachments as $index => $attachment)
                    <label class="flex items-center justify-between bg-slate-50 p-2 rounded border border-slate-200 cursor-pointer">
                        <span class="flex items-center min-w-0">
                            <a href="{{ route('equipment.service.attachment', [$equipment, $service, $index]) }}" class="text-sm font-medium text-slate-700 hover:text-blue-600 truncate">
                                {{ $attachment['original_name'] ?? 'Файл' }}
                            </a>
                            @if(isset($attachment['size']))
                            <span class="text-xs text-slate-500 ml-2 flex-shrink-0">{{ \App\Models\Document::formatBytes($attachment['size']) }}</span>
                            @endif
                        </span>
                        <span class="flex items-center text-sm text-red-600 ml-3 flex-shrink-0">
                            <input type="checkbox" name="remove_attachments[]" value="{{ $index }}" class="mr-1 rounded border-slate-300 text-red-600 focus:ring-red-500">
                            Удалить
                        </span>
                    </label>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Новые вложения -->
            <div class="mb-6">
                <label for="attachments" class="form-label">Добавить файлы</label>
                <div class="flex flex-col items-center justify-center border-2 border-dashed border-slate-300 rounded-lg p-6 bg-slate-50 cursor-pointer" id="dropzone">
                    <div class="flex flex-col items-center">
                        <svg class="w-8 h-8 text-slate-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        <p class="text-sm text-slate-600 mb-1">Перетащите файлы сюда или нажмите для выбора</p>
                        <p class="text-xs text-slate-500">Поддерживаемые форматы: PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, PNG</p>
                    </div>
                    <input type="file" id="attachments" name="new_attachments[]" multiple class="hidden @error('new_attachments') is-invalid @enderror">
                </div>
                <div id="selected-files" class="mt-2 space-y-2"></div>
                @error('new_attachments')
                    <div class="text-red-500 mt-2 text-sm">{{ $message }}</div>
                @enderror
                @error('new_attachments.*')
                    <div class="text-red-500 mt-2 text-sm">{{ $message }}</div>
                @enderror
            </div>

            <!-- Кнопки действий -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <a href="{{ route('equipment.service.show', [$equipment, $service]) }}" class="btn-outline w-full sm:w-auto">Отмена</a>
                <button type="submit" class="btn-primary w-full sm:w-auto">Сохранить изменения</button>
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

        if (!dropzone || !fileInput) {
            return;
        }

        dropzone.addEventListener('click', function() {
            fileInput.click();
        });

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

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

        dropzone.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            const newFileList = new DataTransfer();

            if (fileInput.files.length > 0) {
                for (let i = 0; i < fileInput.files.length; i++) {
                    newFileList.items.add(fileInput.files[i]);
                }
            }

            for (let i = 0; i < files.length; i++) {
                newFileList.items.add(files[i]);
            }

            fileInput.files = newFileList.files;
            updateFileList();
        }

        fileInput.addEventListener('change', updateFileList);

        function updateFileList() {
            selectedFiles.innerHTML = '';

            if (fileInput.files.length > 0) {
                for (let i = 0; i < fileInput.files.length; i++) {
                    const file = fileInput.files[i];
                    const fileSize = formatFileSize(file.size);

                    const fileItem = document.createElement('div');
                    fileItem.className = 'flex items-center justify-between bg-slate-50 p-2 rounded border border-slate-200';

                    fileItem.innerHTML = `
                        <div class="flex items-center">
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

                document.querySelectorAll('.remove-file').forEach(button => {
                    button.addEventListener('click', function() {
                        const index = parseInt(this.dataset.index);
                        removeFile(index);
                    });
                });
            }
        }

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
