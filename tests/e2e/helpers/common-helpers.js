


export async function waitForPageLoad(page, url) {
  await page.goto(url);
  await page.waitForLoadState('networkidle');
}


export async function expectElementVisible(page, selector, text) {
  await page.waitForSelector(selector);
  await expect(page.locator(selector)).toBeVisible();
  if (text) {
    await expect(page.locator(selector)).toContainText(text);
  }
}


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


export async function expectNotification(page, message, type = 'success') {
  const notificationSelector = `.notification-${type}`;
  await page.waitForSelector(notificationSelector);
  await expect(page.locator(notificationSelector)).toContainText(message);
}


export async function expectModal(page, title) {
  await page.waitForSelector('.modal');
  await expect(page.locator('.modal-title')).toContainText(title);
}


export async function closeModal(page) {
  await page.click('.modal-close');
  await page.waitForSelector('.modal', { state: 'hidden' });
}


export async function confirmAction(page, action) {
  await page.click(`button[data-confirm="${action}"]`);
  await page.waitForLoadState('networkidle');
}


export async function waitForTableLoad(page) {
  await page.waitForSelector('table');
  await page.waitForSelector('tbody tr');
}


export async function expectPagination(page) {
  await expect(page.locator('[data-testid="pagination"]')).toBeVisible();
}


export async function goToNextPage(page) {
  await page.click('[data-testid="pagination-next"]');
  await page.waitForLoadState('networkidle');
}


export async function goToPreviousPage(page) {
  await page.click('[data-testid="pagination-prev"]');
  await page.waitForLoadState('networkidle');
}


export async function waitForAjax(page) {
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(500); // Дополнительная задержка для AJAX
}


export async function expectPageTitle(page, title) {
  await expect(page).toHaveTitle(new RegExp(title));
}


export async function expectUrl(page, urlPattern) {
  await expect(page).toHaveURL(new RegExp(urlPattern));
}


export async function clearField(page, selector) {
  await page.focus(selector);
  await page.keyboard.press('Control+a');
  await page.keyboard.press('Delete');
}


export async function scrollToElement(page, selector) {
  await page.locator(selector).scrollIntoViewIfNeeded();
}


export async function waitForElementToDisappear(page, selector) {
  await page.waitForSelector(selector, { state: 'hidden' });
}


export async function expectValidationError(page, field, errorMessage) {
  const errorSelector = `[data-testid="error-${field}"]`;
  await page.waitForSelector(errorSelector);
  await expect(page.locator(errorSelector)).toContainText(errorMessage);
}


export async function expectSaveSuccess(page) {
  await expectNotification(page, 'Сохранено успешно', 'success');
}


export async function expectDeleteSuccess(page) {
  await expectNotification(page, 'Удалено успешно', 'success');
}
