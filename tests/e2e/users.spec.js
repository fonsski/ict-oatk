

import { test, expect } from '@playwright/test';
import { loginAs } from './helpers/auth-helpers.js';
import { testUsers } from './fixtures/test-data.js';

test.describe('Управление пользователями', () => {
  test.beforeEach(async ({ page }) => {
    await loginAs(page, 'admin');
  });

  test('Просмотр списка пользователей', async ({ page }) => {
    await page.goto('/user');
    await expect(page).toHaveTitle(/Пользователи/);
    await expect(page.locator('table')).toBeVisible();
  });

  test('Создание нового пользователя', async ({ page }) => {
    await page.goto('/user/create');
    
    const newUser = {
      name: 'Новый Пользователь',
      phone: '(999) 123-45-88',
      email: 'newuser@test.com',
      password: 'password123',
      role: 'user'
    };
    
    await page.fill('input[name="name"]', newUser.name);
    await page.fill('input[name="phone"]', newUser.phone);
    await page.fill('input[name="email"]', newUser.email);
    await page.fill('input[name="password"]', newUser.password);
    await page.selectOption('select[name="role_id"]', { label: 'Пользователь' });
    
    await page.click('button[type="submit"]');
    
    await expect(page.locator(`text=${newUser.name}`)).toBeVisible();
  });

  test('Редактирование пользователя', async ({ page }) => {
    await page.goto('/user');
    
    // Находим первого пользователя и редактируем
    await page.click('a[href*="/user/"][href*="/edit"]:first-of-type');
    
    const newName = 'Обновленное имя';
    await page.fill('input[name="name"]', newName);
    await page.click('button[type="submit"]');
    
    await expect(page.locator(`text=${newName}`)).toBeVisible();
  });

  test('Изменение роли пользователя', async ({ page }) => {
    await page.goto('/user');
    
    // Находим пользователя и редактируем
    await page.click('a[href*="/user/"][href*="/edit"]:first-of-type');
    
    // Изменяем роль на техника
    await page.selectOption('select[name="role_id"]', { label: 'Техник' });
    await page.click('button[type="submit"]');
    
    await expect(page.locator('text=Техник')).toBeVisible();
  });

  test('Активация/деактивация пользователя', async ({ page }) => {
    await page.goto('/user');
    
    // Находим пользователя и редактируем
    await page.click('a[href*="/user/"][href*="/edit"]:first-of-type');
    
    // Деактивируем пользователя
    await page.uncheck('input[name="is_active"]');
    await page.click('button[type="submit"]');
    
    await expect(page.locator('text=Неактивен')).toBeVisible();
  });

  test('Фильтрация пользователей', async ({ page }) => {
    await page.goto('/user');
    
    // Фильтруем по роли
    await page.selectOption('select[name="role_id"]', { index: 1 });
    await page.click('button[type="submit"]');
    
    // Фильтруем по статусу
    await page.selectOption('select[name="is_active"]', '1');
    await page.click('button[type="submit"]');
  });

  test('Поиск пользователей', async ({ page }) => {
    await page.goto('/user');
    
    // Ищем по имени
    await page.fill('input[name="search"]', 'Тест');
    await page.click('button[type="submit"]');
    await expect(page.locator('text=Тест')).toBeVisible();
  });

  test('Удаление пользователя', async ({ page }) => {
    await page.goto('/user');
    
    // Находим пользователя и удаляем
    await page.click('button[data-action="delete"]:first-of-type');
    await page.click('button[data-confirm="delete"]');
    
    await expect(page.locator('text=Пользователь удален')).toBeVisible();
  });
});

test.describe('Ограничения доступа к пользователям', () => {
  test('Обычный пользователь не может управлять пользователями', async ({ page }) => {
    await loginAs(page, 'user');
    
    // Пытаемся зайти на страницу пользователей
    await page.goto('/user');
    
    // Должны получить 403 ошибку или редирект
    const currentUrl = page.url();
    expect(currentUrl.includes('403') || currentUrl.includes('/login')).toBeTruthy();
  });

  test('Техник не может управлять пользователями', async ({ page }) => {
    await loginAs(page, 'technician');
    
    // Пытаемся зайти на страницу пользователей
    await page.goto('/user');
    
    // Должны получить 403 ошибку или редирект
    const currentUrl = page.url();
    expect(currentUrl.includes('403') || currentUrl.includes('/login')).toBeTruthy();
  });

  test('Мастер может управлять пользователями', async ({ page }) => {
    await loginAs(page, 'master');
    
    await page.goto('/user');
    await expect(page).toHaveTitle(/Пользователи/);
    
    // Должна быть кнопка создания
    await expect(page.locator('a[href*="/user/create"]')).toBeVisible();
  });
});
