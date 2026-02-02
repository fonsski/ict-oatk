

import { test, expect } from '@playwright/test';
import { loginAs, logout, expectAuthenticated, expectNotAuthenticated } from './helpers/auth-helpers.js';
import { testUsers } from './fixtures/test-data.js';

test.describe('Аутентификация', () => {
  test('Главная страница доступна без авторизации', async ({ page }) => {
    await page.goto('/');
    await expect(page).toHaveTitle(/ICT/);
    await expect(page.locator('text=Служба технической поддержки')).toBeVisible();
  });

  test('Страница входа отображается корректно', async ({ page }) => {
    await page.goto('/login');
    await expect(page).toHaveTitle(/Вход/);
    await expect(page.locator('input[name="login"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toBeVisible();
  });

  test('Страница регистрации отображается корректно', async ({ page }) => {
    await page.goto('/register');
    await expect(page).toHaveTitle(/Регистрация/);
    await expect(page.locator('input[name="name"]')).toBeVisible();
    await expect(page.locator('input[name="phone"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    await expect(page.locator('input[name="password_confirmation"]')).toBeVisible();
  });

  test('Вход в систему с корректными данными', async ({ page }) => {
    await loginAs(page, 'user');
    await expectAuthenticated(page);
    await expect(page).toHaveURL('/');
  });

  test('Вход в систему с некорректными данными', async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[name="login"]', '(999) 999-99-99');
    await page.fill('input[name="password"]', 'wrongpassword');
    await page.click('button[type="submit"]');
    
    // Должна остаться на странице входа с ошибкой
    await expect(page).toHaveURL('/login');
    await expect(page.locator('text=Неверный пароль')).toBeVisible();
  });

  test('Выход из системы', async ({ page }) => {
    await loginAs(page, 'user');
    await expectAuthenticated(page);
    
    await logout(page);
    await expectNotAuthenticated(page);
  });

  test('Доступ к защищенным страницам без авторизации', async ({ page }) => {
    // Пытаемся зайти на защищенную страницу
    await page.goto('/tickets');
    
    // Должны быть перенаправлены на страницу входа
    await expect(page).toHaveURL(/.*login/);
  });

  test('Регистрация нового пользователя', async ({ page }) => {
    await page.goto('/register');
    
    const testUser = {
      name: 'Новый Пользователь',
      phone: '(999) 123-45-99',
      password: 'password123'
    };
    
    await page.fill('input[name="name"]', testUser.name);
    await page.fill('input[name="phone"]', testUser.phone);
    await page.fill('input[name="password"]', testUser.password);
    await page.fill('input[name="password_confirmation"]', testUser.password);
    
    await page.click('button[type="submit"]');
    
    // Должны быть перенаправлены на главную страницу
    await expect(page).toHaveURL('/');
    await expectAuthenticated(page);
  });

  test('Восстановление пароля', async ({ page }) => {
    await page.goto('/password/reset');
    await expect(page.locator('input[name="phone"]')).toBeVisible();
    
    await page.fill('input[name="phone"]', '(999) 123-45-67');
    await page.click('button[type="submit"]');
    
    // Должно появиться сообщение об отправке кода
    await expect(page.locator('text=Код подтверждения отправлен на ваш email')).toBeVisible();
  });
});

test.describe('Права доступа по ролям', () => {
  test('Пользователь может видеть только свои заявки', async ({ page }) => {
    await loginAs(page, 'user');
    
    await page.goto('/tickets');
    await expect(page).toHaveTitle(/Мои заявки/);
    
    // Не должно быть ссылки на "Все заявки"
    await expect(page.locator('text=Все заявки').first()).not.toBeVisible();
  });

  test('Техник может видеть все заявки', async ({ page }) => {
    await loginAs(page, 'technician');
    
    await page.goto('/all-tickets');
    await expect(page).toHaveTitle(/Все заявки/);
  });

  test('Администратор имеет доступ ко всем разделам', async ({ page }) => {
    await loginAs(page, 'admin');
    
    // Проверяем доступ к управлению пользователями
    await page.goto('/user');
    await expect(page).toHaveTitle(/Пользователи/);
    
    // Проверяем доступ к управлению оборудованием
    await page.goto('/equipment');
    await expect(page).toHaveTitle(/Оборудование/);
    
    // Проверяем доступ к управлению кабинетами
    await page.goto('/room');
    await expect(page).toHaveTitle(/Кабинеты/);
  });

  test('Обычный пользователь не может получить доступ к админ-разделам', async ({ page }) => {
    await loginAs(page, 'user');
    
    // Пытаемся зайти на страницу пользователей
    await page.goto('/user');
    
    // Должны получить 403 ошибку или редирект
    const currentUrl = page.url();
    expect(currentUrl.includes('403') || currentUrl.includes('/login')).toBeTruthy();
  });
});
