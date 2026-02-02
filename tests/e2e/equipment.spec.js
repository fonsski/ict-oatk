

import { test, expect } from '@playwright/test';
import { loginAs } from './helpers/auth-helpers.js';
import { createEquipment, updateEquipment, moveEquipment, createServiceRecord } from './helpers/equipment-helpers.js';
import { testEquipment } from './fixtures/test-data.js';

test.describe('Управление оборудованием', () => {
  test.beforeEach(async ({ page }) => {
    await loginAs(page, 'admin');
  });

  test('Создание нового оборудования', async ({ page }) => {
    await createEquipment(page, 'computer');
    await expect(page.locator(`text=${testEquipment.computer.name}`)).toBeVisible();
  });

  test('Просмотр списка оборудования', async ({ page }) => {
    await page.goto('/equipment');
    await expect(page).toHaveTitle(/Оборудование/);
    await expect(page.locator('table')).toBeVisible();
  });

  test('Просмотр деталей оборудования', async ({ page }) => {
    // Создаем оборудование
    await createEquipment(page, 'printer');
    
    // Переходим к деталям
    await page.click('a[href*="/equipment/"]');
    await expect(page.locator(`text=${testEquipment.printer.name}`)).toBeVisible();
    await expect(page.locator(`text=${testEquipment.printer.model}`)).toBeVisible();
  });

  test('Редактирование оборудования', async ({ page }) => {
    // Создаем оборудование
    await createEquipment(page, 'computer');
    const equipmentUrl = page.url();
    
    // Переходим к редактированию
    await page.goto(equipmentUrl.replace('/show', '/edit'));
    
    // Изменяем название
    const newName = 'Обновленный компьютер';
    await page.fill('input[name="name"]', newName);
    await page.click('button[type="submit"]');
    
    // Проверяем изменения
    await expect(page.locator(`text=${newName}`)).toBeVisible();
  });

  test('Перемещение оборудования', async ({ page }) => {
    // Создаем оборудование
    await createEquipment(page, 'printer');
    const equipmentUrl = page.url();
    
    // Перемещаем оборудование
    await moveEquipment(page, equipmentUrl.split('/').pop(), '2');
    
    // Проверяем, что оборудование перемещено
    await expect(page.locator('text=Перемещение оборудования')).toBeVisible();
  });

  test('Создание записи обслуживания', async ({ page }) => {
    // Создаем оборудование
    await createEquipment(page, 'computer');
    const equipmentUrl = page.url();
    const equipmentId = equipmentUrl.split('/').pop();
    
    // Создаем запись обслуживания
    const serviceData = {
      date: '2024-01-15',
      description: 'Плановое обслуживание',
      performed_by: 'Техник Иванов',
      cost: '5000'
    };
    
    await createServiceRecord(page, equipmentId, serviceData);
    await expect(page.locator('text=Плановое обслуживание')).toBeVisible();
  });

  test('Фильтрация оборудования', async ({ page }) => {
    await page.goto('/equipment');
    
    // Фильтруем по статусу
    await page.selectOption('select[name="status"]', 'active');
    await page.click('button[type="submit"]');
    await expect(page.locator('text=Активно')).toBeVisible();
    
    // Фильтруем по кабинету
    await page.selectOption('select[name="room_id"]', { index: 1 });
    await page.click('button[type="submit"]');
  });

  test('Поиск оборудования', async ({ page }) => {
    await page.goto('/equipment');
    
    // Ищем по названию
    await page.fill('input[name="search"]', 'Dell');
    await page.click('button[type="submit"]');
    await expect(page.locator('text=Dell')).toBeVisible();
  });
});

test.describe('Управление категориями оборудования', () => {
  test.beforeEach(async ({ page }) => {
    await loginAs(page, 'admin');
  });

  test('Создание категории оборудования', async ({ page }) => {
    await page.goto('/equipment/equipment-categories/create');
    
    await page.fill('input[name="name"]', 'Компьютеры');
    await page.fill('textarea[name="description"]', 'Категория для компьютеров');
    await page.click('button[type="submit"]');
    
    await expect(page.locator('text=Компьютеры')).toBeVisible();
  });

  test('Редактирование категории оборудования', async ({ page }) => {
    await page.goto('/equipment/equipment-categories');
    
    // Находим первую категорию и редактируем
    await page.click('a[href*="/edit"]:first-of-type');
    
    const newName = 'Обновленная категория';
    await page.fill('input[name="name"]', newName);
    await page.click('button[type="submit"]');
    
    await expect(page.locator(`text=${newName}`)).toBeVisible();
  });

  test('Удаление категории оборудования', async ({ page }) => {
    await page.goto('/equipment/equipment-categories');
    
    // Находим первую категорию и удаляем
    await page.click('button[data-action="delete"]:first-of-type');
    await page.click('button[data-confirm="delete"]');
    
    await expect(page.locator('text=Категория удалена')).toBeVisible();
  });
});

test.describe('Ограничения доступа к оборудованию', () => {
  test('Обычный пользователь не может управлять оборудованием', async ({ page }) => {
    await loginAs(page, 'user');
    
    // Пытаемся зайти на страницу оборудования
    await page.goto('/equipment');
    
    // Должны получить 403 ошибку или редирект
    const currentUrl = page.url();
    expect(currentUrl.includes('403') || currentUrl.includes('/login')).toBeTruthy();
  });

  test('Техник может просматривать оборудование', async ({ page }) => {
    await loginAs(page, 'technician');
    
    await page.goto('/equipment');
    await expect(page).toHaveTitle(/Оборудование/);
  });

  test('Мастер может управлять оборудованием', async ({ page }) => {
    await loginAs(page, 'master');
    
    await page.goto('/equipment');
    await expect(page).toHaveTitle(/Оборудование/);
    
    // Должна быть кнопка создания
    await expect(page.locator('a[href*="/equipment/create"]')).toBeVisible();
  });
});
