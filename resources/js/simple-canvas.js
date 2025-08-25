/**
 * Simple Canvas - Простая альтернатива для рисования на canvas
 * Используется как запасной вариант, если Fabric.js не работает
 */

// Глобальная переменная для хранения экземпляра SimpleCanvas
let simpleCanvas = null;

/**
 * Класс SimpleCanvas - базовая обертка над HTML5 Canvas API
 */
class SimpleCanvas {
    /**
     * Конструктор
     * @param {string} canvasId - ID элемента canvas
     * @param {object} options - Опции для canvas
     */
    constructor(canvasId, options = {}) {
        // Находим элемент canvas
        this.canvas = document.getElementById(canvasId);
        if (!this.canvas) {
            throw new Error(`Canvas элемент с ID ${canvasId} не найден`);
        }

        // Получаем 2D контекст для рисования
        this.ctx = this.canvas.getContext('2d');
        if (!this.ctx) {
            throw new Error('Не удалось получить 2D контекст для canvas');
        }

        // Параметры
        this.options = {
            strokeColor: options.strokeColor || '#000000',
            strokeWidth: options.strokeWidth || 2,
            fillColor: options.fillColor || '#ffffff',
            backgroundColor: options.backgroundColor || '#ffffff',
            ...options
        };

        // Переменные для отслеживания состояния
        this.isDrawing = false;
        this.lastX = 0;
        this.lastY = 0;
        this.shapes = [];
        this.currentTool = 'pencil';
        this.selectedShape = null;

        // Устанавливаем начальные размеры canvas
        this._updateCanvasSize();

        // Заполняем canvas фоновым цветом
        this._fillBackground();

        // Инициализируем обработчики событий
        this._initEventListeners();

        console.log('SimpleCanvas успешно инициализирован');
    }

    /**
     * Обновляет размеры canvas в соответствии с родительским элементом
     */
    _updateCanvasSize() {
        const parent = this.canvas.parentElement;
        if (parent) {
            this.canvas.width = parent.offsetWidth;
            this.canvas.height = parent.offsetHeight;
            this._fillBackground();
        }
    }

    /**
     * Заполняет canvas фоновым цветом
     */
    _fillBackground() {
        this.ctx.fillStyle = this.options.backgroundColor;
        this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
    }

    /**
     * Инициализирует обработчики событий
     */
    _initEventListeners() {
        // Обработчики событий мыши
        this.canvas.addEventListener('mousedown', this._handleMouseDown.bind(this));
        this.canvas.addEventListener('mousemove', this._handleMouseMove.bind(this));
        this.canvas.addEventListener('mouseup', this._handleMouseUp.bind(this));
        this.canvas.addEventListener('mouseout', this._handleMouseUp.bind(this));

        // Обработчики событий касания для мобильных устройств
        this.canvas.addEventListener('touchstart', this._handleTouchStart.bind(this));
        this.canvas.addEventListener('touchmove', this._handleTouchMove.bind(this));
        this.canvas.addEventListener('touchend', this._handleTouchEnd.bind(this));

        // Обработчик изменения размера окна
        window.addEventListener('resize', this._handleResize.bind(this));
    }

    /**
     * Обработчик нажатия мыши
     */
    _handleMouseDown(event) {
        const rect = this.canvas.getBoundingClientRect();
        const x = event.clientX - rect.left;
        const y = event.clientY - rect.top;

        this.isDrawing = true;
        this.lastX = x;
        this.lastY = y;

        // Если выбран инструмент фигуры, начинаем рисовать
        if (this.currentTool === 'rect' || this.currentTool === 'circle' || this.currentTool === 'line') {
            this.startShape = { x, y };
        }
    }

    /**
     * Обработчик движения мыши
     */
    _handleMouseMove(event) {
        if (!this.isDrawing) return;

        const rect = this.canvas.getBoundingClientRect();
        const x = event.clientX - rect.left;
        const y = event.clientY - rect.top;

        switch (this.currentTool) {
            case 'pencil':
                this._drawLine(this.lastX, this.lastY, x, y);
                break;
            case 'eraser':
                this._erase(x, y);
                break;
            case 'rect':
                this._previewRect(this.startShape.x, this.startShape.y, x, y);
                break;
            case 'circle':
                this._previewCircle(this.startShape.x, this.startShape.y, x, y);
                break;
            case 'line':
                this._previewLine(this.startShape.x, this.startShape.y, x, y);
                break;
        }

        this.lastX = x;
        this.lastY = y;
    }

    /**
     * Обработчик отпускания кнопки мыши
     */
    _handleMouseUp(event) {
        if (!this.isDrawing) return;

        const rect = this.canvas.getBoundingClientRect();
        const x = event.clientX - rect.left;
        const y = event.clientY - rect.top;

        // Если мы рисовали фигуру, завершаем её
        if (this.currentTool === 'rect') {
            this._drawRect(this.startShape.x, this.startShape.y, x, y);
        } else if (this.currentTool === 'circle') {
            this._drawCircle(this.startShape.x, this.startShape.y, x, y);
        } else if (this.currentTool === 'line') {
            this._drawLine(this.startShape.x, this.startShape.y, x, y);
        }

        this.isDrawing = false;
    }

    /**
     * Обработчик начала касания
     */
    _handleTouchStart(event) {
        event.preventDefault();
        const touch = event.touches[0];
        const mouseEvent = new MouseEvent('mousedown', {
            clientX: touch.clientX,
            clientY: touch.clientY
        });
        this.canvas.dispatchEvent(mouseEvent);
    }

    /**
     * Обработчик движения касания
     */
    _handleTouchMove(event) {
        event.preventDefault();
        const touch = event.touches[0];
        const mouseEvent = new MouseEvent('mousemove', {
            clientX: touch.clientX,
            clientY: touch.clientY
        });
        this.canvas.dispatchEvent(mouseEvent);
    }

    /**
     * Обработчик окончания касания
     */
    _handleTouchEnd(event) {
        event.preventDefault();
        const mouseEvent = new MouseEvent('mouseup', {});
        this.canvas.dispatchEvent(mouseEvent);
    }

    /**
     * Обработчик изменения размера окна
     */
    _handleResize() {
        // Сохраняем текущее состояние canvas
        const imageData = this.ctx.getImageData(0, 0, this.canvas.width, this.canvas.height);

        // Обновляем размеры
        this._updateCanvasSize();

        // Восстанавливаем состояние
        this.ctx.putImageData(imageData, 0, 0);
    }

    /**
     * Рисует линию между двумя точками
     */
    _drawLine(x1, y1, x2, y2) {
        this.ctx.beginPath();
        this.ctx.moveTo(x1, y1);
        this.ctx.lineTo(x2, y2);
        this.ctx.strokeStyle = this.options.strokeColor;
        this.ctx.lineWidth = this.options.strokeWidth;
        this.ctx.lineCap = 'round';
        this.ctx.stroke();
    }

    /**
     * Стирает область вокруг указанной точки
     */
    _erase(x, y) {
        const radius = this.options.strokeWidth * 5;
        this.ctx.globalCompositeOperation = 'destination-out';
        this.ctx.beginPath();
        this.ctx.arc(x, y, radius, 0, Math.PI * 2);
        this.ctx.fill();
        this.ctx.globalCompositeOperation = 'source-over';
    }

    /**
     * Предварительный просмотр прямоугольника
     */
    _previewRect(x1, y1, x2, y2) {
        // Очищаем canvas и восстанавливаем фон
        this._fillBackground();

        // Рисуем прямоугольник
        this.ctx.beginPath();
        this.ctx.rect(
            Math.min(x1, x2),
            Math.min(y1, y2),
            Math.abs(x2 - x1),
            Math.abs(y2 - y1)
        );
        this.ctx.fillStyle = this.options.fillColor;
        this.ctx.fill();
        this.ctx.strokeStyle = this.options.strokeColor;
        this.ctx.lineWidth = this.options.strokeWidth;
        this.ctx.stroke();
    }

    /**
     * Рисует прямоугольник
     */
    _drawRect(x1, y1, x2, y2) {
        this._previewRect(x1, y1, x2, y2);

        // Добавляем фигуру в список фигур
        this.shapes.push({
            type: 'rect',
            x: Math.min(x1, x2),
            y: Math.min(y1, y2),
            width: Math.abs(x2 - x1),
            height: Math.abs(y2 - y1),
            fill: this.options.fillColor,
            stroke: this.options.strokeColor,
            strokeWidth: this.options.strokeWidth
        });
    }

    /**
     * Предварительный просмотр круга
     */
    _previewCircle(x1, y1, x2, y2) {
        // Очищаем canvas и восстанавливаем фон
        this._fillBackground();

        // Вычисляем радиус
        const radius = Math.sqrt(Math.pow(x2 - x1, 2) + Math.pow(y2 - y1, 2));

        // Рисуем круг
        this.ctx.beginPath();
        this.ctx.arc(x1, y1, radius, 0, Math.PI * 2);
        this.ctx.fillStyle = this.options.fillColor;
        this.ctx.fill();
        this.ctx.strokeStyle = this.options.strokeColor;
        this.ctx.lineWidth = this.options.strokeWidth;
        this.ctx.stroke();
    }

    /**
     * Рисует круг
     */
    _drawCircle(x1, y1, x2, y2) {
        this._previewCircle(x1, y1, x2, y2);

        // Вычисляем радиус
        const radius = Math.sqrt(Math.pow(x2 - x1, 2) + Math.pow(y2 - y1, 2));

        // Добавляем фигуру в список фигур
        this.shapes.push({
            type: 'circle',
            x: x1,
            y: y1,
            radius: radius,
            fill: this.options.fillColor,
            stroke: this.options.strokeColor,
            strokeWidth: this.options.strokeWidth
        });
    }

    /**
     * Предварительный просмотр линии
     */
    _previewLine(x1, y1, x2, y2) {
        // Очищаем canvas и восстанавливаем фон
        this._fillBackground();

        // Рисуем линию
        this._drawLine(x1, y1, x2, y2);
    }

    /**
     * Очищает canvas
     */
    clear() {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        this._fillBackground();
        this.shapes = [];
    }

    /**
     * Устанавливает текущий инструмент
     * @param {string} tool - Название инструмента ('pencil', 'eraser', 'rect', 'circle', 'line')
     */
    setTool(tool) {
        this.currentTool = tool;
        console.log('SimpleCanvas: установлен инструмент', tool);
    }

    /**
     * Устанавливает цвет линии
     * @param {string} color - Цвет в формате CSS
     */
    setStrokeColor(color) {
        this.options.strokeColor = color;
    }

    /**
     * Устанавливает толщину линии
     * @param {number} width - Толщина линии в пикселях
     */
    setStrokeWidth(width) {
        this.options.strokeWidth = width;
    }

    /**
     * Устанавливает цвет заливки
     * @param {string} color - Цвет в формате CSS
     */
    setFillColor(color) {
        this.options.fillColor = color;
    }

    /**
     * Экспортирует текущее изображение как Data URL
     * @param {string} type - Тип изображения (по умолчанию 'image/png')
     * @param {number} quality - Качество для 'image/jpeg' (0-1)
     * @returns {string} Data URL
     */
    toDataURL(type = 'image/png', quality = 0.92) {
        return this.canvas.toDataURL(type, quality);
    }

    /**
     * Добавляет изображение на canvas
     * @param {string|File} source - URL изображения или объект File
     * @param {object} options - Опции для изображения
     */
    addImage(source, options = {}) {
        return new Promise((resolve, reject) => {
            try {
                const img = new Image();

                img.onload = () => {
                    const x = options.x || 0;
                    const y = options.y || 0;
                    let width = options.width || img.width;
                    let height = options.height || img.height;

                    // Масштабирование, если указана максимальная ширина
                    if (options.maxWidth && width > options.maxWidth) {
                        const ratio = options.maxWidth / width;
                        width = options.maxWidth;
                        height = height * ratio;
                    }

                    this.ctx.drawImage(img, x, y, width, height);
                    resolve();
                };

                img.onerror = (error) => {
                    reject(error);
                };

                // Если source - это объект File, читаем его как URL
                if (source instanceof File) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        img.src = event.target.result;
                    };
                    reader.onerror = function(error) {
                        reject(error);
                    };
                    reader.readAsDataURL(source);
                } else {
                    // Иначе используем source как URL
                    img.src = source;
                }
            } catch (error) {
                reject(error);
            }
        });
    }
}

/**
 * Инициализирует SimpleCanvas
 * @param {string} canvasId - ID элемента canvas
 * @param {object} options - Опции для canvas
 * @returns {SimpleCanvas} Экземпляр SimpleCanvas
 */
function initializeSimpleCanvas(canvasId, options = {}) {
    try {
        // Если simpleCanvas уже существует, ничего не делаем
        if (simpleCanvas) {
            return simpleCanvas;
        }

        // Создаем новый экземпляр SimpleCanvas
        simpleCanvas = new SimpleCanvas(canvasId, options);
        return simpleCanvas;
    } catch (error) {
        console.error('Ошибка при инициализации SimpleCanvas:', error);
        return null;
    }
}

/**
 * Получает текущий экземпляр SimpleCanvas
 * @returns {SimpleCanvas} Экземпляр SimpleCanvas или null
 */
function getSimpleCanvas() {
    return simpleCanvas;
}

// Экспортируем публичный API
window.simpleCanvasHandler = {
    initializeSimpleCanvas,
    getSimpleCanvas
};

// Автоматическая инициализация при загрузке DOM, если найден canvas-container
document.addEventListener('DOMContentLoaded', function() {
    console.log('simple-canvas.js загружен');
});
