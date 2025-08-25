/**
 * Canvas Handler - Утилита для работы с Fabric.js canvas
 *
 * Этот модуль обеспечивает унифицированный интерфейс для работы с canvas
 * в проекте, используя библиотеку Fabric.js
 */

// Глобальная переменная для хранения экземпляра canvas
let fabricCanvas = null;

/**
 * Проверяет загружена ли библиотека Fabric.js
 * @returns {boolean} true если Fabric.js загружен
 */
function isFabricLoaded() {
    return typeof fabric !== 'undefined';
}

/**
 * Асинхронно загружает библиотеку Fabric.js если она еще не загружена
 * @returns {Promise} Promise, который разрешается, когда Fabric.js загружен
 */
function loadFabricJS() {
    return new Promise((resolve, reject) => {
        // Если библиотека уже загружена, просто разрешаем Promise
        if (isFabricLoaded()) {
            console.log('Fabric.js уже загружен, версия:', fabric.version);
            resolve(true);
            return;
        }

        console.log('Загрузка Fabric.js...');

        // Создаем и добавляем скрипт
        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js';
        script.async = true;

        // Обработчик успешной загрузки
        script.onload = function() {
            console.log('Fabric.js успешно загружен, версия:', fabric.version);
            resolve(true);
        };

        // Обработчик ошибки загрузки
        script.onerror = function(error) {
            console.error('Не удалось загрузить Fabric.js:', error);
            reject(new Error('Не удалось загрузить Fabric.js'));
        };

        document.head.appendChild(script);
    });
}

/**
 * Инициализирует canvas в указанном контейнере
 * @param {string} canvasId - ID элемента canvas
 * @param {object} options - Опции для инициализации canvas
 * @returns {Promise} Promise с созданным экземпляром canvas
 */
function initializeCanvas(canvasId, options = {}) {
    return new Promise(async (resolve, reject) => {
        try {
            // Загружаем Fabric.js, если он еще не загружен
            await loadFabricJS();

            // Получаем элемент canvas
            const canvasElement = document.getElementById(canvasId);
            if (!canvasElement) {
                throw new Error(`Canvas элемент с ID ${canvasId} не найден`);
            }

            // Получаем родительский контейнер
            const container = canvasElement.parentElement;
            if (!container) {
                throw new Error('Не найден родительский контейнер для canvas');
            }

            // Задаем размеры canvas на основе размеров контейнера
            const width = options.width || container.offsetWidth || 800;
            const height = options.height || container.offsetHeight || 600;

            // Если экземпляр canvas уже существует, удаляем его
            if (fabricCanvas) {
                fabricCanvas.dispose();
                fabricCanvas = null;
            }

            // Создаем новый экземпляр canvas
            fabricCanvas = new fabric.Canvas(canvasId, {
                width: width,
                height: height,
                backgroundColor: options.backgroundColor || '#fff',
                selection: options.selection !== undefined ? options.selection : true,
                isDrawingMode: options.isDrawingMode !== undefined ? options.isDrawingMode : false,
                renderOnAddRemove: true,
                fireRightClick: true,
                stopContextMenu: true,
                ...options
            });

            console.log('Canvas инициализирован:', fabricCanvas);

            // Добавляем обработчик изменения размера окна
            window.addEventListener('resize', handleResize);

            // Возвращаем экземпляр canvas через Promise
            setTimeout(() => {
                fabricCanvas.calcOffset();
                fabricCanvas.renderAll();
                resolve(fabricCanvas);
            }, 100);
        } catch (error) {
            console.error('Ошибка при инициализации canvas:', error);
            reject(error);
        }
    });
}

/**
 * Обработчик изменения размера окна
 */
function handleResize() {
    if (!fabricCanvas) return;

    const canvasElement = fabricCanvas.getElement();
    if (!canvasElement) return;

    const container = canvasElement.parentElement;
    if (!container) return;

    // Получаем новые размеры контейнера
    const width = container.offsetWidth;
    const height = container.offsetHeight;

    // Устанавливаем новые размеры canvas
    fabricCanvas.setWidth(width);
    fabricCanvas.setHeight(height);

    // Обновляем координаты всех объектов
    fabricCanvas.forEachObject(function(obj) {
        obj.setCoords();
    });

    fabricCanvas.calcOffset();
    fabricCanvas.renderAll();
}

/**
 * Загружает данные в canvas из JSON
 * @param {object|string} json - JSON данные для загрузки
 * @returns {Promise} Promise, который разрешается, когда данные загружены
 */
function loadCanvasFromJSON(json) {
    return new Promise((resolve, reject) => {
        if (!fabricCanvas) {
            reject(new Error('Canvas не инициализирован'));
            return;
        }

        try {
            // Парсим JSON, если передана строка
            const jsonData = typeof json === 'string' ? JSON.parse(json) : json;

            // Загружаем данные в canvas
            fabricCanvas.loadFromJSON(jsonData, function() {
                // Обновляем координаты всех объектов
                fabricCanvas.forEachObject(function(obj) {
                    obj.setCoords();
                });

                fabricCanvas.calcOffset();
                fabricCanvas.renderAll();
                console.log('Данные canvas успешно загружены');
                resolve(fabricCanvas);
            });
        } catch (error) {
            console.error('Ошибка при загрузке данных canvas:', error);
            reject(error);
        }
    });
}

/**
 * Экспортирует текущее состояние canvas в JSON
 * @returns {string} JSON-строка с данными canvas
 */
function exportCanvasToJSON() {
    if (!fabricCanvas) {
        throw new Error('Canvas не инициализирован');
    }

    return JSON.stringify(fabricCanvas.toJSON());
}

/**
 * Очищает canvas
 */
function clearCanvas() {
    if (!fabricCanvas) return;

    fabricCanvas.clear();
    fabricCanvas.backgroundColor = '#fff';
    fabricCanvas.calcOffset();
    fabricCanvas.renderAll();
}

/**
 * Получает текущий экземпляр canvas
 * @returns {fabric.Canvas} Экземпляр canvas или null
 */
function getCanvas() {
    return fabricCanvas;
}

/**
 * Добавляет форму на canvas
 * @param {string} shapeType - Тип формы ('rect', 'circle', 'line', 'text')
 * @param {object} options - Опции для создания формы
 */
function addShape(shapeType, options = {}) {
    if (!fabricCanvas) return null;

    let shape;

    switch (shapeType) {
        case 'rect':
            shape = new fabric.Rect({
                left: options.left || 100,
                top: options.top || 100,
                width: options.width || 100,
                height: options.height || 100,
                fill: options.fill || '#ffffff',
                stroke: options.stroke || '#000000',
                strokeWidth: options.strokeWidth || 2,
                ...options
            });
            break;
        case 'circle':
            shape = new fabric.Circle({
                left: options.left || 100,
                top: options.top || 100,
                radius: options.radius || 50,
                fill: options.fill || '#ffffff',
                stroke: options.stroke || '#000000',
                strokeWidth: options.strokeWidth || 2,
                ...options
            });
            break;
        case 'line':
            shape = new fabric.Line([
                options.x1 || 100,
                options.y1 || 100,
                options.x2 || 200,
                options.y2 || 100
            ], {
                stroke: options.stroke || '#000000',
                strokeWidth: options.strokeWidth || 2,
                ...options
            });
            break;
        case 'text':
            shape = new fabric.IText(options.text || 'Текст', {
                left: options.left || 100,
                top: options.top || 100,
                fontFamily: options.fontFamily || 'Arial',
                fontSize: options.fontSize || 16,
                fill: options.fill || '#000000',
                ...options
            });
            break;
        default:
            console.error('Неизвестный тип формы:', shapeType);
            return null;
    }

    fabricCanvas.add(shape);
    shape.setCoords();
    fabricCanvas.renderAll();

    return shape;
}

/**
 * Устанавливает режим рисования
 * @param {boolean} enable - Включить/выключить режим рисования
 * @param {object} options - Опции кисти
 */
function setDrawingMode(enable, options = {}) {
    if (!fabricCanvas) return;

    fabricCanvas.isDrawingMode = enable;

    if (enable) {
        // Создаем кисть если её нет
        if (!fabricCanvas.freeDrawingBrush) {
            fabricCanvas.freeDrawingBrush = new fabric.PencilBrush(fabricCanvas);
        }

        // Настраиваем кисть
        fabricCanvas.freeDrawingBrush.color = options.color || '#000000';
        fabricCanvas.freeDrawingBrush.width = options.width || 2;
    }
}

/**
 * Добавляет изображение на canvas
 * @param {string|File} source - URL изображения или объект File
 * @param {object} options - Опции для изображения
 * @returns {Promise} Promise с добавленным изображением
 */
function addImage(source, options = {}) {
    return new Promise((resolve, reject) => {
        if (!fabricCanvas) {
            reject(new Error('Canvas не инициализирован'));
            return;
        }

        try {
            // Если source - это объект File, читаем его как DataURL
            if (source instanceof File) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    fabric.Image.fromURL(event.target.result, function(img) {
                        if (options.maxWidth) {
                            img.scaleToWidth(options.maxWidth);
                        }

                        fabricCanvas.add(img);
                        img.setCoords();
                        fabricCanvas.renderAll();
                        resolve(img);
                    }, options);
                };
                reader.onerror = function(error) {
                    reject(error);
                };
                reader.readAsDataURL(source);
            } else {
                // Иначе используем source как URL
                fabric.Image.fromURL(source, function(img) {
                    if (options.maxWidth) {
                        img.scaleToWidth(options.maxWidth);
                    }

                    fabricCanvas.add(img);
                    img.setCoords();
                    fabricCanvas.renderAll();
                    resolve(img);
                }, options);
            }
        } catch (error) {
            console.error('Ошибка при добавлении изображения:', error);
            reject(error);
        }
    });
}

// Экспортируем публичный API
window.canvasHandler = {
    isFabricLoaded,
    loadFabricJS,
    initializeCanvas,
    loadCanvasFromJSON,
    exportCanvasToJSON,
    clearCanvas,
    getCanvas,
    addShape,
    setDrawingMode,
    addImage
};

// Автоматическая инициализация при загрузке DOM, если найден canvas-container
document.addEventListener('DOMContentLoaded', function() {
    console.log('canvas-handler.js загружен');
});
