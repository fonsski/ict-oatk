# Компонент счетчика символов

## Описание

Универсальный компонент для отображения счетчика символов в полях заголовков, обеспечивающий единообразие во всех формах системы.

## Использование

### Автоматическая инициализация

Добавьте атрибут `data-char-counter` к любому полю ввода:

```html
<input type="text" 
       id="title" 
       name="title" 
       data-char-counter
       data-max-length="60"
       data-min-length="5"
       data-warning-threshold="50"
       data-help-text="Минимум 5, максимум 60 символов">
```

### Атрибуты конфигурации

- `data-char-counter` - включает компонент
- `data-max-length` - максимальное количество символов (по умолчанию: 60)
- `data-min-length` - минимальное количество символов (по умолчанию: 5)
- `data-warning-threshold` - порог предупреждения (по умолчанию: 50)
- `data-help-text` - текст подсказки
- `data-counter-id` - ID для элемента счетчика

### Программная инициализация

```javascript
const charCounter = new CharCounter(document.getElementById('title'), {
    maxLength: 100,
    minLength: 5,
    warningThreshold: 80,
    helpText: 'Минимум 5, максимум 100 символов'
});
```

## Функциональность

### Визуальные индикаторы

- **Серый цвет** - нормальное количество символов
- **Оранжевый цвет** - приближение к лимиту (после warningThreshold)
- **Красный цвет** - превышение лимита

### Валидация

- Автоматическая проверка длины ввода
- Визуальная индикация при превышении лимита
- Изменение цвета границы поля при ошибке

### Публичные методы

```javascript
// Получить текущую длину
const length = charCounter.getCurrentLength();

// Получить оставшиеся символы
const remaining = charCounter.getRemainingChars();

// Проверить валидность
const isValid = charCounter.isValid();
```

## Примеры использования

### Заявки (60 символов)
```html
<input type="text" 
       name="title" 
       data-char-counter
       data-max-length="60"
       data-min-length="5"
       data-warning-threshold="50"
       data-help-text="Минимум 5, максимум 60 символов">
```

### FAQ (100 символов)
```html
<input type="text" 
       name="title" 
       data-char-counter
       data-max-length="100"
       data-min-length="5"
       data-warning-threshold="80"
       data-help-text="Минимум 5, максимум 100 символов">
```

### База знаний (255 символов)
```html
<input type="text" 
       name="title" 
       data-char-counter
       data-max-length="255"
       data-min-length="5"
       data-warning-threshold="200"
       data-help-text="Минимум 5, максимум 255 символов">
```

## Интеграция

Компонент автоматически подключается через `resources/js/app.js` и инициализируется при загрузке страницы для всех полей с атрибутом `data-char-counter`.
