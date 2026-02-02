import "./bootstrap";
import "./form-validation";
import "./loading-states";
import "./notifications";
import "./modals";
import "./accessibility";
import "./char-counter";
import "./inventory-number-helper";


window.initPhoneMasks = function () {
    // Проверяем, загружена ли библиотека IMask
    if (typeof IMask === "undefined") {
        loadIMaskLibrary();
        return;
    }

    // Находим все поля ввода телефона
    const phoneInputs = document.querySelectorAll(
        'input[type="tel"], input[name="phone"], input[id="phone"], input[id="reporter_phone"], input[name="login"]',
    );

    if (phoneInputs.length === 0) return;

    // Применяем маску к каждому найденному полю
    phoneInputs.forEach((input) => {
        const maskOptions = {
            mask: "+7 (000) 000-00-00",
            lazy: false,
            placeholderChar: "_",
            overwrite: true,
        };

        const mask = IMask(input, maskOptions);

        // Автоматически добавляем +7 при фокусе, если поле пустое
        input.addEventListener("focus", function () {
            if (!this.value) {
                mask.value = "+7 ";
            }
        });

        // Убедимся, что пользователь может нажимать на любую позицию поля
        input.addEventListener("click", function (e) {
            // Если курсор находится перед +7, переместим его после +7
            if (this.selectionStart < 3) {
                this.setSelectionRange(3, 3);
            }
        });

        // Разрешаем удаление всего содержимого и обрабатываем ввод
        input.addEventListener("keydown", function (e) {
            // Разрешаем backspace и delete для удаления всех символов
            if (
                (e.key === "Backspace" || e.key === "Delete") &&
                (this.value === "+7 " || this.value === "+7 (")
            ) {
                this.value = "";
                e.preventDefault();
            }

            // Если пользователь нажимает число, когда курсор находится перед +7,
            // переместим курсор после +7 перед вводом числа
            const isDigit = /^\d$/.test(e.key);
            if (isDigit && this.selectionStart < 3) {
                this.setSelectionRange(3, 3);
            }
        });

        // Визуальная валидация при изменении
        input.addEventListener("input", function () {
            if (
                this.value &&
                !this.value.match(/^\+7 \(\d{3}\) \d{3}-\d{2}-\d{2}$/)
            ) {
                this.classList.add(
                    "border-red-300",
                    "focus:ring-red-500",
                    "focus:border-red-500",
                );
            } else {
                this.classList.remove(
                    "border-red-300",
                    "focus:ring-red-500",
                    "focus:border-red-500",
                );
            }
        });
    });
};


function loadIMaskLibrary() {
    if (window.IMask) {
        initPhoneMasks();
        return;
    }

    const script = document.createElement("script");
    script.src = "https://unpkg.com/imask@6.4.3/dist/imask.min.js";
    script.onload = function () {
        window.initPhoneMasks();
    };
    document.head.appendChild(script);
}

// Инициализируем маски при загрузке страницы
document.addEventListener("DOMContentLoaded", function () {
    window.initPhoneMasks();
});
// Убираем конфликтующие импорты для canvas
// import "./canvas-handler";

