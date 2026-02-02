

import { test, expect } from '@playwright/test';
import { loginAs } from './helpers/auth-helpers.js';

test.describe('Тесты производительности', () => {
  test('Время загрузки главной страницы', async ({ page }) => {
    const startTime = Date.now();
    await page.goto('/');
    await page.waitForLoadState('networkidle');
    const loadTime = Date.now() - startTime;
    
    // Главная страница должна загружаться менее чем за 2 секунды
    expect(loadTime).toBeLessThan(2000);
    console.log(`Время загрузки главной страницы: ${loadTime}ms`);
  });

  test('Время загрузки страницы входа', async ({ page }) => {
    const startTime = Date.now();
    await page.goto('/login');
    await page.waitForLoadState('networkidle');
    const loadTime = Date.now() - startTime;
    
    // Страница входа должна загружаться менее чем за 1 секунду
    expect(loadTime).toBeLessThan(1000);
    console.log(`Время загрузки страницы входа: ${loadTime}ms`);
  });

  test('Время входа в систему', async ({ page }) => {
    await page.goto('/login');
    
    const startTime = Date.now();
    await page.fill('input[name="login"]', '(999) 123-45-67');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/');
    const loginTime = Date.now() - startTime;
    
    // Вход в систему должен происходить менее чем за 3 секунды
    expect(loginTime).toBeLessThan(3000);
    console.log(`Время входа в систему: ${loginTime}ms`);
  });

  test('Время загрузки списка заявок', async ({ page }) => {
    await loginAs(page, 'user');
    
    const startTime = Date.now();
    await page.goto('/tickets');
    await page.waitForLoadState('networkidle');
    const loadTime = Date.now() - startTime;
    
    // Список заявок должен загружаться менее чем за 2 секунды
    expect(loadTime).toBeLessThan(2000);
    console.log(`Время загрузки списка заявок: ${loadTime}ms`);
  });

  test('Время создания заявки', async ({ page }) => {
    await loginAs(page, 'user');
    
    const startTime = Date.now();
    await page.goto('/tickets/create');
    await page.fill('input[name="title"]', 'Тестовая заявка производительности');
    await page.selectOption('select[name="category"]', 'hardware');
    await page.selectOption('select[name="priority"]', 'medium');
    await page.fill('textarea[name="description"]', 'Описание тестовой заявки');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/ticketsequipmentknowledge/*');
    const createTime = Date.now() - startTime;
    
    // Создание статьи должно происходить менее чем за 3 секунды
    expect(createTime).toBeLessThan(3000);
    console.log(`Время создания статьи: ${createTime}ms`);
  });

  test('Время загрузки уведомлений', async ({ page }) => {
    await loginAs(page, 'technician');
    
    const startTime = Date.now();
    await page.click('#notifications-menu-button');
    await page.waitForSelector('#notifications-dropdown');
    const loadTime = Date.now() - startTime;
    
    // Уведомления должны загружаться менее чем за 1 секунду
    expect(loadTime).toBeLessThan(1000);
    console.log(`Время загрузки уведомлений: ${loadTime}ms`);
  });

  test('Время фильтрации заявок', async ({ page }) => {
    await loginAs(page, 'technician');
    
    const startTime = Date.now();
    await page.goto('/all-tickets');
    await page.selectOption('select[name="status"]', 'open');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    const filterTime = Date.now() - startTime;
    
    // Фильтрация должна выполняться менее чем за 2 секунды
    expect(filterTime).toBeLessThan(2000);
    console.log(`Время фильтрации заявок: ${filterTime}ms`);
  });

  test('Время пагинации', async ({ page }) => {
    await loginAs(page, 'admin');
    
    const startTime = Date.now();
    await page.goto('/equipment');
    await page.click('a[href*="page="]');
    await page.waitForLoadState('networkidle');
    const paginationTime = Date.now() - startTime;
    
    // Пагинация должна выполняться менее чем за 1 секунду
    expect(paginationTime).toBeLessThan(1000);
    console.log(`Время пагинации: ${paginationTime}ms`);
  });
});
