import "./bootstrap";
import "./canvas-handler";
import "./simple-canvas";

// Основные скрипты приложения
document.addEventListener("DOMContentLoaded", function () {
    // Проверка canvas-container на странице
    const canvasContainer = document.getElementById("canvas-container");
    if (canvasContainer) {
        console.log("Обнаружен canvas-container, инициализируем холст...");

        // Проверка доступности canvas-handler
        if (typeof window.canvasHandler !== "undefined") {
            console.log("Canvas Handler успешно загружен");

            // Загружаем Fabric.js если нужно
            window.canvasHandler
                .loadFabricJS()
                .then(() => {
                    console.log("Fabric.js готов к использованию");
                })
                .catch((error) => {
                    console.error("Ошибка при загрузке Fabric.js:", error);

                    // Если Fabric.js не загрузился, пробуем использовать простой canvas
                    trySimpleCanvas();
                });
        } else {
            // Если canvas-handler недоступен, пробуем использовать простой canvas
            trySimpleCanvas();
        }
    }

    // Функция для инициализации простого canvas
    function trySimpleCanvas() {
        if (typeof window.simpleCanvasHandler !== "undefined") {
            console.log(
                "Пробуем использовать Simple Canvas как запасной вариант",
            );
            const canvasId = document.querySelector(
                "#canvas-container canvas",
            )?.id;
            if (canvasId) {
                window.simpleCanvasHandler.initializeSimpleCanvas(canvasId);
            }
        }
    }
});
