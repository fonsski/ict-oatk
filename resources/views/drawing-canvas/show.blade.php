@extends('layouts.app')

@section('title', $drawing->title)

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 mb-2">{{ $drawing->title }}</h1>
            @if($drawing->description)
            <p class="text-slate-600">{{ $drawing->description }}</p>
            @endif
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('drawing-canvas.edit', $drawing) }}"
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200">
                <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
                Редактировать
            </a>
            <a href="{{ route('drawing-canvas.index') }}"
                class="inline-flex items-center px-4 py-2 text-slate-600 hover:text-slate-900 transition-colors duration-200">
                <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Назад к списку
            </a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4 text-sm text-slate-500">
            <div class="flex items-center space-x-4">
                <span>
                    <svg class="w-4 h-4 inline-block mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    Создан: {{ $drawing->created_at->format('d.m.Y H:i') }}
                </span>
                @if($drawing->updated_at && $drawing->updated_at->ne($drawing->created_at))
                <span>
                    <svg class="w-4 h-4 inline-block mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                    Обновлен: {{ $drawing->updated_at->format('d.m.Y H:i') }}
                </span>
                @endif
            </div>
            <div>
                <span class="px-3 py-1 bg-slate-100 text-slate-700 rounded-full">
                    {{ ucfirst($drawing->type) }}
                </span>
            </div>
        </div>

        <div id="canvas-container" class="w-full h-[70vh] overflow-auto border border-slate-200 bg-white">
            <canvas id="drawing-canvas" class="w-full h-full"></canvas>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация холста fabric.js в режиме только для чтения
    const canvas = new fabric.Canvas('drawing-canvas', {
        width: document.getElementById('canvas-container').offsetWidth,
        height: document.getElementById('canvas-container').offsetHeight,
        selection: false,
        interactive: false,
        backgroundColor: '#fff'
    });

    // Запрещаем интерактивность всех объектов
    function disableInteractivity() {
        canvas.forEachObject(function(obj) {
            obj.selectable = false;
            obj.evented = false;
        });
    }

    // Загружаем данные холста из JSON
    try {
        const canvasData = @json($drawing->canvas_data);
        canvas.loadFromJSON(canvasData, function() {
            canvas.renderAll();
            disableInteractivity();
        });
    } catch (error) {
        console.error('Ошибка загрузки данных холста:', error);
        // Show error message next to the canvas
        const container = document.getElementById('canvas-container');
        if (container) {
            container.innerHTML = '<div class="flex items-center justify-center h-full"><p class="text-red-500">Ошибка загрузки чертежа. Возможно, данные повреждены.</p></div>';
        }
    }

    // Обработка изменения размера окна
    window.addEventListener('resize', function() {
        const container = document.getElementById('canvas-container');
        if (container) {
            canvas.setWidth(container.offsetWidth);
            canvas.setHeight(container.offsetHeight);
            canvas.renderAll();
        }
    });
});
</script>
@endpush
