/**
 * Общие вспомогательные функции для тестов
 */

/**
 * Ожидание загрузки страницы
 * @param {import('@playwright/test').Page} page
 * @param {string} url
 */
export async function waitForPageLoad(page, url) {
  await page.goto(url);
  await page.waitForLoadState('networkidle');
}

/**
 * Проверка наличия элемента на странице
 * @param {import('@playwright/test').Page} page
 * @param {string} selector
 * @param {string} text
 */
export async function expectElementVisible(page, selector, text) {
  await page.waitForSelector(selector);
  await expect(page.locator(selector)).toBeVisible();
  if (text) {
    await expect(page.locator(selector)).toContainText(text);
  }
}

/**
 * Заполнение формы
 * @param {import('@playwright/test').Page} page
 * @param {Object} formData
 */
export async function fillForm(page, formData) {
  for (const [field, value] of Object.entries(formData)) {
    const selector = `input[name="${field}"], textarea[name="${field}"], select[name="${field}"]`;
    const element = page.locator(selector);
    
    if (await element.isVisible()) {
      const tagName = await element.evaluate(el => el.tagName.toLowerCase());
      
      if (tagName === 'select') {
        await element.selectOption(value);
      } else {
        await element.fill(value);
      }
    }
  }
}

/**
 * Ожидание уведомления
 * @param {import('@playwright/test').Page} page
 * @param {string} message
 * @param {string} type
 */
export async function expectNotification(page, message, type = 'success') {
  const notificationSelector = `.notification-${type}`;
  await page.waitForSelector(notificationSelector);
  await expect(page.locator(notificationSelector)).toContainText(message);
}

/**
 * Ожидание модального окна
 * @param {import('@playwright/test').Page} page
 * @param {string} title
 */
export async function expectModal(page, title) {
  await page.waitForSelector('.modal');
  await expect(page.locator('.modal-title')).toContainText(title);
}

/**
 * Закрытие модального окна
 * @param {import('@playwright/test').Page} page
 */
export async function closeModal(page) {
  await page.click('.modal-close');
  await page.waitForSelector('.modal', { state: 'hidden' });
}

/**
 * Подтверждение действия
 * @param {import('@playwright/test').Page} page
 * @param {string} action
 */
export async function confirmAction(page, action) {
  await page.click(`button[data-confirm="${action}"]`);
  await page.waitForLoadState('networkidle');
}

/**
 * Ожидание загрузки таблицы
 * @param {import('@playwright/test').Page} page
 */
export async function waitForTableLoad(page) {
  await page.waitForSelector('table');
  await page.waitForSelector('tbody tr');
}

/**
 * Проверка пагинации
 * @param {import('@playwright/test').Page} page
 */
export async function expectPagination(page) {
  await expect(page.locator('[data-testid="pagination"]')).toBeVisible();
}

/**
 * Переход на следующую страницу
 * @param {import('@playwright/test').Page} page
 */
export async function goToNextPage(page) {
  await page.click('[data-testid="pagination-next"]');
  await page.waitForLoadState('networkidle');
}

/**
 * Переход на предыдущую страницу
 * @param {import('@playwright/test').Page} page
 */
export async function goToPreviousPage(page) {
  await page.click('[data-testid="pagination-prev"]');
  await page.waitForLoadState('networkidle');
}

/**
 * Ожидание загрузки AJAX запроса
 * @param {import('@playwright/test').Page} page
 */
export async function waitForAjax(page) {
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(500); // Дополнительная задержка для AJAX
}

/**
 * Проверка заголовка страницы
 * @param {import('@playwright/test').Page} page
 * @param {string} title
 */
export async function expectPageTitle(page, title) {
  await expect(page).toHaveTitle(new RegExp(title));
}

/**
 * Проверка URL страницы
 * @param {import('@playwright/test').Page} page
 * @param {string} urlPattern
 */
export async function expectUrl(page, urlPattern) {
  await expect(page).toHaveURL(new RegExp(urlPattern));
}

/**
 * Очистка поля ввода
 * @param {import('@playwright/test').Page} page
 * @param {string} selector
 */
export async function clearField(page, selector) {
  await page.focus(selector);
  await page.keyboard.press('Control+a');
  await page.keyboard.press('Delete');
}

/**
 * Скролл к элементу
 * @param {import('@playwright/test').Page} page
 * @param {string} selector
 */
export async function scrollToElement(page, selector) {
  await page.locator(selector).scrollIntoViewIfNeeded();
}

/**
 * Ожидание исчезновения элемента
 * @param {import('@playwright/test').Page} page
 * @param {string} selector
 */
export async function waitForElementToDisappear(page, selector) {
  await page.waitForSelector(selector, { state: 'hidden' });
}

/**
 * Проверка наличия ошибок валидации
 * @param {import('@playwright/test').Page} page
 * @param {string} field
 * @param {string} errorMessage
 */
export async function expectValidationError(page, field, errorMessage) {
  const errorSelector = `[data-testid="error-${field}"]`;
  await page.waitForSelector(errorSelector);
  await expect(page.locator(errorSelector)).toContainText(errorMessage);
}

/**
 * Ожидание успешного сохранения
 * @param {import('@playwright/test').Page} page
 */
export async function expectSaveSuccess(page) {
  await expectNotification(page, 'Сохранено успешно', 'success');
}

/**
 * Ожидание успешного удаления
 * @param {import('@playwright/test').Page} page
 */
export async function expectDeleteSuccess(page) {
  await expectNotification(page, 'Удалено успешно', 'success');
}
