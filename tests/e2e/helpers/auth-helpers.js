/**
 * Вспомогательные функции для аутентификации
 */

import { testUsers } from '../fixtures/test-data.js';

/**
 * Вход в систему
 * @param {import('@playwright/test').Page} page
 * @param {string} userRole - роль пользователя (admin, master, technician, user)
 */
export async function loginAs(page, userRole) {
  const user = testUsers[userRole];
  
  await page.goto('/login');
  await page.waitForLoadState('networkidle');
  
  // Заполняем форму входа
  await page.fill('input[name="login"]', user.phone);
  await page.fill('input[name="password"]', user.password);
  
  // Нажимаем кнопку входа
  await page.click('button[type="submit"]');
  
  // Ждем перенаправления на главную страницу
  await page.waitForURL('**/');
  await page.waitForLoadState('networkidle');
}

/**
 * Выход из системы
 * @param {import('@playwright/test').Page} page
 */
export async function logout(page) {
  // Находим кнопку выхода в меню пользователя
  await page.click('#user-menu-button');
  await page.click('a[href*="logout"]');
  
  // Ждем перенаправления на страницу входа
  await page.waitForURL('**/login');
}

/**
 * Проверяем, что пользователь авторизован
 * @param {import('@playwright/test').Page} page
 */
export async function expectAuthenticated(page) {
  await page.waitForSelector('#user-menu-button');
}

/**
 * Проверяем, что пользователь не авторизован
 * @param {import('@playwright/test').Page} page
 */
export async function expectNotAuthenticated(page) {
  await page.waitForSelector('input[name="login"]');
}

/**
 * Проверяем доступ к странице по роли
 * @param {import('@playwright/test').Page} page
 * @param {string} url
 * @param {string} expectedRole
 */
export async function expectAccessByRole(page, url, expectedRole) {
  await page.goto(url);
  
  // Если пользователь не имеет доступа, должен быть редирект на 403 или логин
  const currentUrl = page.url();
  if (currentUrl.includes('/login') || currentUrl.includes('403')) {
    throw new Error(`Пользователь с ролью ${expectedRole} не имеет доступа к ${url}`);
  }
}
