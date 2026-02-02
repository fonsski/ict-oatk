# Smart Updates - Руководство по использованию

## Обзор

Smart Updates - это умная система обновления данных, которая обновляет только измененные элементы без перерисовки всей страницы. Это решает проблему исчезновения открытых меню и других состояний UI при автоматическом обновлении данных.

## Основные преимущества

- ✅ **Сохранение состояния UI** - открытые меню, формы, фокус остаются на месте
- ✅ **Производительность** - обновляются только измененные элементы
- ✅ **Универсальность** - можно использовать для любых списков данных
- ✅ **Простота использования** - минимальная настройка

## Установка

1. Добавьте файл в Vite конфиг:
```javascript
"resources/js/smart-updates.js"
```

2. Пересоберите ресурсы:
```bash
npm run build
```

## Использование

### Базовый пример

```javascript
// Инициализация SmartUpdates
const smartUpdates = new SmartUpdates({
    containerSelector: '#my-list',           // Селектор контейнера
    itemSelector: '.list-item',              // Селектор элементов списка
    itemIdAttribute: 'data-id',             // Атрибут с ID элемента
    createItemHTML: createItemHTML,          // Функция создания HTML
    fieldsToCheck: ['name', 'status'],      // Поля для проверки изменений
    preserveState: true                      // Сохранять состояние UI
});

// Обновление данных
smartUpdates.updateData(newData);
```

### Пример для таблицы заявок

```javascript
const smartUpdates = new SmartUpdates({
    containerSelector: '#tickets-tbody',
    itemSelector: 'tr[data-ticket-id]',
    itemIdAttribute: 'data-ticket-id',
    createItemHTML: createTicketRow,
    fieldsToCheck: ['status', 'priority', 'assigned_to_name'],
    preserveState: true,
    onItemUpdate: function(ticket) {
        // Переинициализируем обработчики для обновленной строки
        initTableDropdowns();
    }
});
```

### Пример для списка пользователей

```javascript
const smartUpdates = new SmartUpdates({
    containerSelector: '#users-list',
    itemSelector: '.user-item',
    itemIdAttribute: 'data-user-id',
    createItemHTML: createUserItem,
    fieldsToCheck: ['name', 'email', 'is_active'],
    preserveState: true
});
```

## Опции конфигурации

| Опция | Тип | Описание |
|-------|-----|----------|
| `containerSelector` | string | Селектор контейнера с элементами |
| `itemSelector` | string | Селектор отдельных элементов |
| `itemIdAttribute` | string | Атрибут с ID элемента (по умолчанию: 'data-id') |
| `createItemHTML` | function | Функция создания HTML для элемента |
| `fieldsToCheck` | array | Поля для проверки изменений |
| `preserveState` | boolean | Сохранять состояние UI элементов |
| `onItemUpdate` | function | Callback при обновлении элемента |
| `onItemAdd` | function | Callback при добавлении элемента |
| `onItemRemove` | function | Callback при удалении элемента |

## Интеграция с LiveUpdates

```javascript
// В LiveUpdates onSuccess callback
onSuccess: function(data) {
    if (data.tickets && Array.isArray(data.tickets)) {
        smartUpdates.updateData(data.tickets);
    }
}
```

## Сохранение состояния

SmartUpdates автоматически сохраняет и восстанавливает:

- Открытые выпадающие меню
- Классы элементов
- Позицию прокрутки
- Другие состояния UI

## Методы

### `updateData(newData)`
Обновляет данные с умным механизмом.

### `clear()`
Очищает все сохраненные данные.

## Примеры использования в системе

### 1. Страница всех заявок (`/all-tickets`)
```javascript
smartUpdates = new SmartUpdates({
    containerSelector: '#tickets-tbody',
    itemSelector: 'tr[data-ticket-id]',
    itemIdAttribute: 'data-ticket-id',
    createItemHTML: createTicketRow,
    fieldsToCheck: ['status', 'priority', 'assigned_to_name', 'assigned_to_role'],
    preserveState: true
});
```

### 2. Страница пользователей (`/users`)
```javascript
smartUpdates = new SmartUpdates({
    containerSelector: '#users-list',
    itemSelector: '.user-item',
    itemIdAttribute: 'data-user-id',
    createItemHTML: createUserItem,
    fieldsToCheck: ['name', 'email', 'is_active', 'role'],
    preserveState: true
});
```

### 3. Страница оборудования (`/equipment`)
```javascript
smartUpdates = new SmartUpdates({
    containerSelector: '#equipment-list',
    itemSelector: '.equipment-item',
    itemIdAttribute: 'data-equipment-id',
    createItemHTML: createEquipmentItem,
    fieldsToCheck: ['name', 'status', 'location'],
    preserveState: true
});
```

## Миграция существующего кода

### Было:
```javascript
function updateTable(data) {
    const tbody = document.getElementById('table-body');
    tbody.innerHTML = data.map(item => createRow(item)).join('');
    initEventHandlers();
}
```

### Стало:
```javascript
const smartUpdates = new SmartUpdates({
    containerSelector: '#table-body',
    itemSelector: 'tr[data-id]',
    itemIdAttribute: 'data-id',
    createItemHTML: createRow,
    fieldsToCheck: ['status', 'name'],
    preserveState: true
});

function updateTable(data) {
    smartUpdates.updateData(data);
}
```

## Отладка

Для отладки добавьте логирование:

```javascript
const smartUpdates = new SmartUpdates({
    // ... опции
    onItemUpdate: function(item) {
        console.log('Обновлен элемент:', item.id);
    },
    onItemAdd: function(item) {
        console.log('Добавлен элемент:', item.id);
    },
    onItemRemove: function(item) {
        console.log('Удален элемент:', item.id);
    }
});
```

## Производительность

SmartUpdates оптимизирован для производительности:

- Использует Map для быстрого поиска элементов
- Сравнивает только указанные поля
- Обновляет только измененные элементы
- Сохраняет состояние только при необходимости

## Совместимость

- Современные браузеры (ES6+)
- Поддерживает все современные JavaScript функции
- Работает с любыми фреймворками и библиотеками
