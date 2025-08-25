@extends('layouts.app')

@section('title', 'Создание нового чертежа')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 mb-2">Создание нового чертежа</h1>
            <p class="text-slate-600">Используйте инструменты рисования для создания схем и диаграмм</p>
        </div>
        <a href="{{ route('drawing-canvas.index') }}"
            class="inline-flex items-center px-4 py-2 text-slate-600 hover:text-slate-900 transition-colors duration-200">
            <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Назад к списку
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
        <form action="{{ route('drawing-canvas.store') }}" method="POST" id="drawing-form" class="p-0">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-0">
                <!-- Панель с инструментами слева -->
                <div class="p-4 border-r border-slate-200 bg-slate-50">
                    <div class="mb-6">
                        <label for="title" class="block text-sm font-medium text-slate-700 mb-2">
                            Название <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               id="title"
                               name="title"
                               class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               value="{{ old('title') }}"
                               required>
                        @error('title')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label for="description" class="block text-sm font-medium text-slate-700 mb-2">
                            Описание
                        </label>
                        <textarea
                            id="description"
                            name="description"
                            rows="3"
                            class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >{{ old('description') }}</textarea>
                        @error('description')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label for="type" class="block text-sm font-medium text-slate-700 mb-2">
                            Тип чертежа
                        </label>
                        <select
                            id="type"
                            name="type"
                            class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="general" {{ old('type') == 'general' ? 'selected' : '' }}>Общий</option>
                            <option value="network" {{ old('type') == 'network' ? 'selected' : '' }}>Сетевая диаграмма</option>
                            <option value="floorplan" {{ old('type') == 'floorplan' ? 'selected' : '' }}>План помещения</option>
                            <option value="infrastructure" {{ old('type') == 'infrastructure' ? 'selected' : '' }}>Инфраструктура</option>
                        </select>
                    </div>

                    <hr class="my-6 border-slate-200">

                    <div class="mb-4">
                        <h3 class="font-medium text-slate-800 mb-3">Инструменты</h3>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" class="tool-btn p-2 border border-slate-300 rounded hover:bg-slate-100" data-tool="select">
                                <svg class="w-6 h-6 mx-auto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 3l7 7m0 0v-6m0 6h-6"></path>
                                </svg>
                                <span class="text-xs block mt-1">Выбрать</span>
                            </button>
                            <button type="button" class="tool-btn p-2 border border-slate-300 rounded hover:bg-slate-100" data-tool="shape">
                                <svg class="w-6 h-6 mx-auto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                                </svg>
                                <span class="text-xs block mt-1">Фигура</span>
                            </button>
                            <button type="button" class="tool-btn p-2 border border-slate-300 rounded hover:bg-slate-100" data-tool="line">
                                <svg class="w-6 h-6 mx-auto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                <span class="text-xs block mt-1">Линия</span>
                            </button>
                            <button type="button" class="tool-btn p-2 border border-slate-300 rounded hover:bg-slate-100" data-tool="text">
                                <svg class="w-6 h-6 mx-auto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <text x="5" y="16" font-size="14">T</text>
                                </svg>
                                <span class="text-xs block mt-1">Текст</span>
                            </button>
                            <button type="button" class="tool-btn p-2 border border-slate-300 rounded hover:bg-slate-100" data-tool="eraser">
                                <svg class="w-6 h-6 mx-auto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 20H4m8-12l4 4m0 0l-8 8m8-8l-8-8m8 8L8 20"></path>
                                </svg>
                                <span class="text-xs block mt-1">Ластик</span>
                            </button>
                            <button type="button" class="tool-btn p-2 border border-slate-300 rounded hover:bg-slate-100" data-tool="image">
                                <svg class="w-6 h-6 mx-auto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                    <polyline points="21 15 16 10 5 21"></polyline>
                                </svg>
                                <span class="text-xs block mt-1">Изображение</span>
                            </button>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h3 class="font-medium text-slate-800 mb-3">Свойства</h3>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs text-slate-600 mb-1">Цвет линии</label>
                                <input type="color" id="stroke-color" value="#000000" class="w-full h-8 p-0 border">
                            </div>
                            <div>
                                <label class="block text-xs text-slate-600 mb-1">Толщина линии</label>
                                <input type="range" id="stroke-width" min="1" max="20" value="2" class="w-full">
                            </div>
                            <div>
                                <label class="block text-xs text-slate-600 mb-1">Цвет заливки</label>
                                <input type="color" id="fill-color" value="#ffffff" class="w-full h-8 p-0 border">
                            </div>
                        </div>
                    </div>

                    <hr class="my-6 border-slate-200">

                    <div class="flex flex-col space-y-3">
                        <button type="button" id="clear-canvas" class="w-full px-3 py-2 bg-red-50 text-red-600 border border-red-200 rounded-md hover:bg-red-100 transition-colors">
                            Очистить холст
                        </button>

                        <button type="button" id="save-drawing" class="w-full px-3 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                            Сохранить чертёж
                        </button>
                    </div>
                </div>

                <!-- Холст для рисования -->
                <div class="col-span-3 h-[80vh] bg-white relative">
                    <div id="canvas-container" class="w-full h-full overflow-auto">
                        <canvas id="drawing-board" class="w-full h-full bg-gray-50 border border-dashed border-gray-300" style="touch-action: none;"></canvas>
                    </div>
                </div>
            </div>

            <!-- Скрытое поле для хранения данных холста -->
            <input type="hidden" name="canvas_data" id="canvas_data" required>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<style>
    /* Стили для холста */
    #canvas-container {
        position: relative;
        background-color: #f8f9fa;
        border-radius: 4px;
    }
    canvas {
        cursor: crosshair;
        touch-action: none;
    }
    .tool-btn.active {
        background-color: #e0f2fe;
        border-color: #3b82f6;
        color: #1e40af;
    }
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
<script>
// Глобальные переменные
let canvas;
let currentTool = 'select';
let isCanvasReady = false;

// Настройки
const settings = {
    strokeColor: '#000000',
    strokeWidth: 2,
    fillColor: '#ffffff'
};

// Инициализация при загрузке DOM
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM загружен, инициализация canvas...');

    // Задержка для обеспечения полной загрузки DOM
    setTimeout(initCanvas, 300);

    // Обработчики событий для инструментов
    setupTools();
});

// Инициализация canvas
function initCanvas() {
    try {
        // Получаем контейнер
        const container = document.getElementById('canvas-container');
        if (!container) {
            console.error('Ошибка: элемент canvas-container не найден');
            return;
        }

        // Находим canvas
        const canvasEl = document.getElementById('drawing-board');
        if (!canvasEl) {
            console.error('Ошибка: элемент drawing-board не найден');
            return;
        }

        // Получаем размеры
        const width = container.offsetWidth;
        const height = container.offsetHeight;

        // Создаем новый canvas
        canvas = new fabric.Canvas('drawing-board', {
            width: width,
            height: height,
            backgroundColor: '#ffffff',
            selection: true,
            isDrawingMode: false
        });

        console.log('Canvas создан успешно', { width, height });
        isCanvasReady = true;

        // Обработчик для создания объектов при клике
        canvas.on('mouse:down', handleCanvasClick);

        // Обработчик изменения размера окна
        window.addEventListener('resize', handleResize);

        // Устанавливаем активный инструмент
        setActiveTool('select');

        // Финальная настройка
        canvas.renderAll();

    } catch (error) {
        console.error('Ошибка при инициализации canvas:', error);
        alert('Произошла ошибка при создании холста. Пожалуйста, перезагрузите страницу.');
    }
}

// Обработчик клика по холсту
function handleCanvasClick(options) {
    if (!canvas || !isCanvasReady) return;

    const pointer = canvas.getPointer(options.e);

    switch (currentTool) {
        case 'shape':
            // Создаем прямоугольник
            const rect = new fabric.Rect({
                left: pointer.x,
                top: pointer.y,
                width: 100,
                height: 100,
                fill: settings.fillColor,
                stroke: settings.strokeColor,
                strokeWidth: settings.strokeWidth
            });
            canvas.add(rect);
            canvas.setActiveObject(rect);
            break;

        case 'line':
            // Создаем линию
            const line = new fabric.Line([
                pointer.x, pointer.y,
                pointer.x + 100, pointer.y
            ], {
                stroke: settings.strokeColor,
                strokeWidth: settings.strokeWidth
            });
            canvas.add(line);
            canvas.setActiveObject(line);
            break;

        case 'text':
            // Создаем текст
            const text = new fabric.IText('Двойной клик для редактирования', {
                left: pointer.x,
                top: pointer.y,
                fontFamily: 'Arial',
                fill: settings.strokeColor,
                fontSize: 16
            });
            canvas.add(text);
            canvas.setActiveObject(text);
            break;
    }

    // Обновляем холст
    canvas.renderAll();
}

// Обработчик изменения размера окна
function handleResize() {
    if (!canvas || !isCanvasReady) return;

    const container = document.getElementById('canvas-container');
    if (container) {
        canvas.setWidth(container.offsetWidth);
        canvas.setHeight(container.offsetHeight);
        canvas.renderAll();
    }
}

// Настройка инструментов
function setupTools() {
    // Кнопки инструментов
    document.querySelectorAll('.tool-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const tool = this.dataset.tool;
            setActiveTool(tool);
        });
    });

    // Настройка цвета линии
    document.getElementById('stroke-color').addEventListener('input', function(e) {
        settings.strokeColor = e.target.value;
        updateActiveObject();
    });

    // Настройка толщины линии
    document.getElementById('stroke-width').addEventListener('input', function(e) {
        settings.strokeWidth = parseInt(e.target.value);
        updateActiveObject();
    });

    // Настройка цвета заливки
    document.getElementById('fill-color').addEventListener('input', function(e) {
        settings.fillColor = e.target.value;
        updateActiveObject();
    });

    // Кнопка очистки холста
    document.getElementById('clear-canvas').addEventListener('click', function() {
        if (confirm('Вы уверены, что хотите очистить холст? Все несохранённые изменения будут потеряны.')) {
            clearCanvas();
        }
    });

    // Кнопка сохранения
    document.getElementById('save-drawing').addEventListener('click', function() {
        saveDrawing();
    });
}

// Установка активного инструмента
function setActiveTool(tool) {
    if (!canvas) return;

    currentTool = tool;

    // Сбросить выбор всех кнопок
    document.querySelectorAll('.tool-btn').forEach(btn => {
        btn.classList.remove('active');
    });

    // Выделить текущую кнопку
    const activeBtn = document.querySelector(`.tool-btn[data-tool="${tool}"]`);
    if (activeBtn) {
        activeBtn.classList.add('active');
    }

    // Настройка режима холста
    switch(tool) {
        case 'select':
            canvas.isDrawingMode = false;
            canvas.selection = true;
            break;
        case 'shape':
        case 'line':
        case 'text':
            canvas.isDrawingMode = false;
            canvas.selection = false;
            break;
        case 'eraser':
            canvas.isDrawingMode = true;
            if (!canvas.freeDrawingBrush) {
                canvas.freeDrawingBrush = new fabric.PencilBrush(canvas);
            }
            canvas.freeDrawingBrush.color = '#ffffff';
            canvas.freeDrawingBrush.width = 20;
            break;
        case 'image':
            addImage();
            break;
    }
}

// Обновление свойств активного объекта
function updateActiveObject() {
    if (!canvas) return;

    const obj = canvas.getActiveObject();
    if (!obj) return;

    if (obj.stroke !== undefined) {
        obj.set('stroke', settings.strokeColor);
    }

    if (obj.strokeWidth !== undefined) {
        obj.set('strokeWidth', settings.strokeWidth);
    }

    if (obj.fill !== undefined && obj.type !== 'i-text') {
        obj.set('fill', settings.fillColor);
    }

    canvas.renderAll();
}

// Очистка холста
function clearCanvas() {
    if (!canvas) return;

    canvas.clear();
    canvas.backgroundColor = '#ffffff';
    canvas.renderAll();
}

// Сохранение рисунка
function saveDrawing() {
    if (!canvas) return;

    try {
        // Получаем JSON-представление холста
        const canvasData = JSON.stringify(canvas.toJSON());

        // Сохраняем в скрытом поле формы
        document.getElementById('canvas_data').value = canvasData;

        // Проверяем заполнение обязательных полей
        const title = document.getElementById('title').value.trim();
        if (!title) {
            alert('Пожалуйста, укажите название чертежа');
            return;
        }

        // Отправляем форму
        document.getElementById('drawing-form').submit();
    } catch (error) {
        console.error('Ошибка при сохранении рисунка:', error);
        alert('Произошла ошибка при сохранении. Пожалуйста, попробуйте еще раз.');
    }
}

// Добавление изображения
function addImage() {
    if (!canvas) return;

    // Открыть диалог выбора файла
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';

    input.onchange = function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(f) {
                const data = f.target.result;
                fabric.Image.fromURL(data, function(img) {
                    img.scaleToWidth(200);
                    canvas.add(img);
                    canvas.renderAll();
                });
            };
            reader.readAsDataURL(file);
        }
    };

    input.click();
}
</script>
@endpush
