/**
 * Тесты управления кабинетами
 */

import { test, expect } from '@playwright/test';
import { loginAs } from './helpers/auth-helpers.js';
import { createRoom, updateRoom, changeRoomStatus } from './helpers/room-helpers.js';
import { testRooms } from './fixtures/test-data.js';

test.describe('Управление кабинетами', () => {
  test.beforeEach(async ({ page }) => {
    await loginAs(page, 'admin');
  });

  test('Создание нового кабинета', async ({ page }) => {
    await createRoom(page, 'room101');
    await expect(page.locator(`text=${testRooms.room101.number}`)).toBeVisible();
  });

  test('Просмотр списка кабинетов', async ({ page }) => {
    await page.goto('/room');
    await expect(page).toHaveTitle(/Кабинеты/);
    await expect(page.locator('table')).toBeVisible();
  });

  test('Просмотр деталей кабинета', async ({ page }) => {
    // Создаем кабинет
    await createRoom(page, 'room205');
    
    // Переходим к деталям
    await page.click('a[href*="/room/"]');
    await expect(page.locator(`text=${testRooms.room205.number}`)).toBeVisible();
    await expect(page.locator(`text=${testRooms.room205.name}`)).toBeVisible();
  });

  test('Редактирование кабинета', async ({ page }) => {
    // Создаем кабинет
    await createRoom(page, 'room101');
    const roomUrl = page.url();
    
    // Переходим к редактированию
    await page.goto(roomUrl.replace('/show', '/edit'));
    
    // Изменяем название
    const newName = 'Обновленный кабинет';
    await page.fill('input[name="name"]', newName);
    await page.click('button[type="submit"]');
    
    // Проверяем изменения
    await expect(page.locator(`text=${newName}`)).toBeVisible();
  });

  test('Изменение статуса кабинета', async ({ page }) => {
    // Создаем кабинет
    await createRoom(page, 'room205');
    const roomUrl = page.url();
    const roomId = roomUrl.split('/').pop();
    
    // Изменяем статус
    await changeRoomStatus(page, roomId, 'maintenance');
    await expect(page.locator('text=На обслуживании')).toBeVisible();
  });

  test('Фильтрация кабинетов', async ({ page }) => {
    await page.goto('/room');
    
    // Фильтруем по типу
    await page.selectOption('select[name="type"]', 'office');
    await page.click('button[type="submit"]');
    
    // Фильтруем по зданию
    await page.selectOption('select[name="building"]', { index: 1 });
    await page.click('button[type="submit"]');
  });

  test('Поиск кабинетов', async ({ page }) => {
    await page.goto('/room');
    
    // Ищем по номеру
    await page.fill('input[name="search"]', '101');
    await page.click('button[type="submit"]');
    await expect(page.locator('text=101')).toBeVisible();
  });

  test('Просмотр оборудования в кабинете', async ({ page }) => {
    // Создаем кабинет
    await createRoom(page, 'room101');
    const roomUrl = page.url();
    
    // Переходим к деталям кабинета
    await page.goto(roomUrl);
    
    // Проверяем раздел оборудования
    await expect(page.locator('text=Оборудование в кабинете')).toBeVisible();
  });
});

test.describe('Ограничения доступа к кабинетам', () => {
  test('Обычный пользователь не может управлять кабинетами', async ({ page }) => {
    await loginAs(page, 'user');
    
    // Пытаемся зайти на страницу кабинетов
    await page.goto('/room');
    
    // Должны получить 403 ошибку или редирект
    const currentUrl = page.url();
    expect(currentUrl.includes('403') || currentUrl.includes('/login')).toBeTruthy();
  });

  test('Техник может просматривать кабинеты', async ({ page }) => {
    await loginAs(page, 'technician');
    
    await page.goto('/room');
    await expect(page).toHaveTitle(/Кабинеты/);
  });

  test('Мастер может управлять кабинетами', async ({ page }) => {
    await loginAs(page, 'master');
    
    await page.goto('/room');
    await expect(page).toHaveTitle(/Кабинеты/);
    
    // Должна быть кнопка создания
    await expect(page.locator('a[href*="/room/create"]')).toBeVisible();
  });
});
