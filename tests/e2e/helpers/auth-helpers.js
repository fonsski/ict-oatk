

import { testUsers } from '../fixtures/test-data.js';


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


export async function logout(page) {
  // Находим кнопку выхода в меню пользователя
  await page.click('#user-menu-button');
  await page.click('a[href*="logout"]');
  
  // Ждем перенаправления на страницу входа
  await page.waitForURL('**/login');
}


export async function expectAuthenticated(page) {
  await page.waitForSelector('#user-menu-button');
}


export async function expectNotAuthenticated(page) {
  await page.waitForSelector('input[name="login"]');
}


export async function expectAccessByRole(page, url, expectedRole) {
  await page.goto(url);
  
  // Если пользователь не имеет доступа, должен быть редирект на 403 или логин
  const currentUrl = page.url();
  if (currentUrl.includes('/login') || currentUrl.includes('403')) {
    throw new Error(`Пользователь с ролью ${expectedRole} не имеет доступа к ${url}`);
  }
}
