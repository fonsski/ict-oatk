/**
 * Вспомогательные функции для проверок и утверждений
 */

import { expect } from '@playwright/test';

/**
 * Проверка наличия всех элементов навигации
 * @param {import('@playwright/test').Page} page
 * @param {Array<string>} expectedLinks
 */
export async function expectNavigationLinks(page, expectedLinks) {
  for (const link of expectedLinks) {
    await expect(page.locator(`a[href*="${link}"]`)).toBeVisible();
  }
}

/**
 * Проверка отсутствия элементов навигации
 * @param {import('@playwright/test').Page} page
 * @param {Array<string>} forbiddenLinks
 */
export async function expectNoNavigationLinks(page, forbiddenLinks) {
  for (const link of forbiddenLinks) {
    await expect(page.locator(`a[href*="${link}"]`)).not.toBeVisible();
  }
}

/**
 * Проверка таблицы с данными
 * @param {import('@playwright/test').Page} page
 * @param {Array<string>} expectedColumns
 * @param {number} minRows
 */
export async function expectTableWithData(page, expectedColumns, minRows = 1) {
  // Проверяем наличие таблицы
  await expect(page.locator('table')).toBeVisible();
  
  // Проверяем заголовки колонок
  for (const column of expectedColumns) {
    await expect(page.locator(`th:has-text("${column}")`)).toBeVisible();
  }
  
  // Проверяем наличие строк данных
  const rows = page.locator('tbody tr');
  await expect(rows).toHaveCount({ min: minRows });
}

/**
 * Проверка формы с полями
 * @param {import('@playwright/test').Page} page
 * @param {Array<string>} expectedFields
 */
export async function expectFormFields(page, expectedFields) {
  for (const field of expectedFields) {
    const selector = `input[name="${field}"], textarea[name="${field}"], select[name="${field}"]`;
    await expect(page.locator(selector)).toBeVisible();
  }
}

/**
 * Проверка статуса заявки
 * @param {import('@playwright/test').Page} page
 * @param {string} expectedStatus
 */
export async function expectTicketStatus(page, expectedStatus) {
  const statusMap = {
    'open': 'Открыта',
    'in_progress': 'В работе',
    'resolved': 'Решена',
    'closed': 'Закрыта'
  };
  
  const statusText = statusMap[expectedStatus] || expectedStatus;
  await expect(page.locator(`text=${statusText}`)).toBeVisible();
}

/**
 * Проверка приоритета заявки
 * @param {import('@playwright/test').Page} page
 * @param {string} expectedPriority
 */
export async function expectTicketPriority(page, expectedPriority) {
  const priorityMap = {
    'low': 'Низкий',
    'medium': 'Средний',
    'high': 'Высокий',
    'urgent': 'Срочный'
  };
  
  const priorityText = priorityMap[expectedPriority] || expectedPriority;
  await expect(page.locator(`text=${priorityText}`)).toBeVisible();
}

/**
 * Проверка категории заявки
 * @param {import('@playwright/test').Page} page
 * @param {string} expectedCategory
 */
export async function expectTicketCategory(page, expectedCategory) {
  const categoryMap = {
    'hardware': 'Оборудование',
    'software': 'Программное обеспечение',
    'network': 'Сеть и интернет',
    'account': 'Учетная запись',
    'other': 'Другое'
  };
  
  const categoryText = categoryMap[expectedCategory] || expectedCategory;
  await expect(page.locator(`text=${categoryText}`)).toBeVisible();
}

/**
 * Проверка роли пользователя
 * @param {import('@playwright/test').Page} page
 * @param {string} expectedRole
 */
export async function expectUserRole(page, expectedRole) {
  const roleMap = {
    'admin': 'Администратор',
    'master': 'Мастер',
    'technician': 'Техник',
    'user': 'Пользователь'
  };
  
  const roleText = roleMap[expectedRole] || expectedRole;
  await expect(page.locator(`text=${roleText}`)).toBeVisible();
}

/**
 * Проверка статуса оборудования
 * @param {import('@playwright/test').Page} page
 * @param {string} expectedStatus
 */
export async function expectEquipmentStatus(page, expectedStatus) {
  const statusMap = {
    'active': 'Активно',
    'inactive': 'Неактивно',
    'maintenance': 'На обслуживании',
    'broken': 'Сломано'
  };
  
  const statusText = statusMap[expectedStatus] || expectedStatus;
  await expect(page.locator(`text=${statusText}`)).toBeVisible();
}

/**
 * Проверка типа кабинета
 * @param {import('@playwright/test').Page} page
 * @param {string} expectedType
 */
export async function expectRoomType(page, expectedType) {
  const typeMap = {
    'office': 'Офис',
    'classroom': 'Учебный класс',
    'laboratory': 'Лаборатория',
    'conference': 'Конференц-зал',
    'storage': 'Склад'
  };
  
  const typeText = typeMap[expectedType] || expectedType;
  await expect(page.locator(`text=${typeText}`)).toBeVisible();
}

/**
 * Проверка наличия кнопок действий
 * @param {import('@playwright/test').Page} page
 * @param {Array<string>} expectedActions
 */
export async function expectActionButtons(page, expectedActions) {
  for (const action of expectedActions) {
    await expect(page.locator(`button[data-action="${action}"]`)).toBeVisible();
  }
}

/**
 * Проверка отсутствия кнопок действий
 * @param {import('@playwright/test').Page} page
 * @param {Array<string>} forbiddenActions
 */
export async function expectNoActionButtons(page, forbiddenActions) {
  for (const action of forbiddenActions) {
    await expect(page.locator(`button[data-action="${action}"]`)).not.toBeVisible();
  }
}

/**
 * Проверка фильтров
 * @param {import('@playwright/test').Page} page
 * @param {Array<string>} expectedFilters
 */
export async function expectFilters(page, expectedFilters) {
  for (const filter of expectedFilters) {
    await expect(page.locator(`select[name="${filter}"], input[name="${filter}"]`)).toBeVisible();
  }
}

/**
 * Проверка поиска
 * @param {import('@playwright/test').Page} page
 */
export async function expectSearchField(page) {
  await expect(page.locator('input[name="search"]')).toBeVisible();
}

/**
 * Проверка пагинации
 * @param {import('@playwright/test').Page} page
 */
export async function expectPaginationControls(page) {
  await expect(page.locator('.pagination')).toBeVisible();
  await expect(page.locator('.pagination-info')).toBeVisible();
}

/**
 * Проверка сортировки
 * @param {import('@playwright/test').Page} page
 * @param {Array<string>} sortableColumns
 */
export async function expectSortableColumns(page, sortableColumns) {
  for (const column of sortableColumns) {
    await expect(page.locator(`th[data-sort="${column}"]`)).toBeVisible();
  }
}

/**
 * Проверка массовых операций
 * @param {import('@playwright/test').Page} page
 * @param {Array<string>} bulkActions
 */
export async function expectBulkActions(page, bulkActions) {
  await expect(page.locator('input[type="checkbox"][name="select_all"]')).toBeVisible();
  
  for (const action of bulkActions) {
    await expect(page.locator(`select[name="bulk_action"] option[value="${action}"]`)).toBeVisible();
  }
}

/**
 * Проверка уведомлений
 * @param {import('@playwright/test').Page} page
 * @param {string} message
 * @param {string} type
 */
export async function expectNotificationMessage(page, message, type = 'success') {
  const notificationSelector = `.notification-${type}`;
  await expect(page.locator(notificationSelector)).toBeVisible();
  await expect(page.locator(notificationSelector)).toContainText(message);
}

/**
 * Проверка модального окна
 * @param {import('@playwright/test').Page} page
 * @param {string} title
 * @param {Array<string>} expectedFields
 */
export async function expectModalForm(page, title, expectedFields = []) {
  await expect(page.locator('.modal')).toBeVisible();
  await expect(page.locator('.modal-title')).toContainText(title);
  
  for (const field of expectedFields) {
    const selector = `input[name="${field}"], textarea[name="${field}"], select[name="${field}"]`;
    await expect(page.locator(selector)).toBeVisible();
  }
}

/**
 * Проверка загрузки
 * @param {import('@playwright/test').Page} page
 */
export async function expectLoadingIndicator(page) {
  await expect(page.locator('.loading')).toBeVisible();
}

/**
 * Проверка отсутствия загрузки
 * @param {import('@playwright/test').Page} page
 */
export async function expectNoLoadingIndicator(page) {
  await expect(page.locator('.loading')).not.toBeVisible();
}

/**
 * Проверка пустого состояния
 * @param {import('@playwright/test').Page} page
 * @param {string} message
 */
export async function expectEmptyState(page, message) {
  await expect(page.locator('.empty-state')).toBeVisible();
  await expect(page.locator('.empty-state')).toContainText(message);
}

/**
 * Проверка ошибки
 * @param {import('@playwright/test').Page} page
 * @param {string} errorMessage
 */
export async function expectError(page, errorMessage) {
  await expect(page.locator('.error')).toBeVisible();
  await expect(page.locator('.error')).toContainText(errorMessage);
}
