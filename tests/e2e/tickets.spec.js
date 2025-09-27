/**
 * Тесты управления заявками
 */

import { test, expect } from '@playwright/test';
import { loginAs } from './helpers/auth-helpers.js';
import { createTicket, changeTicketStatus, assignTicket, addComment, filterTickets } from './helpers/ticket-helpers.js';
import { testTickets } from './fixtures/test-data.js';

test.describe('Управление заявками', () => {
  test.beforeEach(async ({ page }) => {
    await loginAs(page, 'user');
  });

  test('Создание новой заявки', async ({ page }) => {
    await createTicket(page, 'hardware');
    await expect(page.locator(`text=${testTickets.hardware.title}`)).toBeVisible();
  });

  test('Просмотр списка заявок', async ({ page }) => {
    await page.goto('/tickets');
    await expect(page).toHaveTitle(/Мои заявки/);
    await expect(page.locator('table')).toBeVisible();
  });

  test('Просмотр деталей заявки', async ({ page }) => {
    // Сначала создаем заявку
    await createTicket(page, 'software');
    
    // Переходим к деталям заявки
    await page.click('a[href*="/tickets/"]');
    await expect(page.locator(`text=${testTickets.software.title}`)).toBeVisible();
    await expect(page.locator(`text=${testTickets.software.description}`)).toBeVisible();
  });

  test('Редактирование заявки', async ({ page }) => {
    // Создаем заявку
    await createTicket(page, 'network');
    
    // Переходим к редактированию
    await page.click('a[href*="/edit"]');
    
    // Изменяем описание
    const newDescription = 'Обновленное описание проблемы';
    await page.fill('textarea[name="description"]', newDescription);
    await page.click('button[type="submit"]');
    
    // Проверяем, что изменения сохранились
    await expect(page.locator(`text=${newDescription}`)).toBeVisible();
  });
});

test.describe('Управление заявками (техник)', () => {
  test.beforeEach(async ({ page }) => {
    await loginAs(page, 'technician');
  });

  test('Просмотр всех заявок', async ({ page }) => {
    await page.goto('/all-tickets');
    await expect(page).toHaveTitle(/Все заявки/);
    await expect(page.locator('table')).toBeVisible();
  });

  test('Изменение статуса заявки', async ({ page }) => {
    // Создаем заявку от имени пользователя
    await loginAs(page, 'user');
    await createTicket(page, 'hardware');
    const ticketUrl = page.url();
    
    // Переключаемся на техника
    await loginAs(page, 'technician');
    await page.goto(ticketUrl);
    
    // Изменяем статус на "В работе"
    await changeTicketStatus(page, '', 'in_progress');
    await expect(page.locator('text=В работе')).toBeVisible();
  });

  test('Назначение исполнителя заявке', async ({ page }) => {
    // Создаем заявку
    await loginAs(page, 'user');
    await createTicket(page, 'software');
    const ticketUrl = page.url();
    
    // Переключаемся на техника
    await loginAs(page, 'technician');
    await page.goto(ticketUrl);
    
    // Назначаем исполнителя
    await assignTicket(page, '', 'Техник Тест');
    await expect(page.locator('text=Техник Тест')).toBeVisible();
  });

  test('Добавление комментария к заявке', async ({ page }) => {
    // Создаем заявку
    await loginAs(page, 'user');
    await createTicket(page, 'network');
    const ticketUrl = page.url();
    
    // Переключаемся на техника
    await loginAs(page, 'technician');
    await page.goto(ticketUrl);
    
    // Добавляем комментарий
    const comment = 'Начинаю работу над заявкой';
    await addComment(page, '', comment);
    await expect(page.locator(`text=${comment}`)).toBeVisible();
  });

  test('Фильтрация заявок', async ({ page }) => {
    await page.goto('/all-tickets');
    
    // Фильтруем по статусу
    await filterTickets(page, { status: 'open' });
    await expect(page.locator('text=Открыта')).toBeVisible();
    
    // Фильтруем по приоритету
    await filterTickets(page, { priority: 'high' });
    await expect(page.locator('text=Высокий')).toBeVisible();
    
    // Фильтруем по категории
    await filterTickets(page, { category: 'hardware' });
    await expect(page.locator('text=Оборудование')).toBeVisible();
  });

  test('Поиск заявок', async ({ page }) => {
    await page.goto('/all-tickets');
    
    // Ищем по номеру телефона
    await filterTickets(page, { search: '999' });
    await expect(page.locator('text=999')).toBeVisible();
  });
});

test.describe('Управление заявками (администратор)', () => {
  test.beforeEach(async ({ page }) => {
    await loginAs(page, 'admin');
  });

  test('Полный цикл обработки заявки', async ({ page }) => {
    // Создаем заявку от имени пользователя
    await loginAs(page, 'user');
    await createTicket(page, 'urgent');
    const ticketUrl = page.url();
    
    // Переключаемся на администратора
    await loginAs(page, 'admin');
    await page.goto(ticketUrl);
    
    // 1. Назначаем исполнителя
    await assignTicket(page, '', 'Техник Тест');
    
    // 2. Изменяем статус на "В работе"
    await changeTicketStatus(page, '', 'in_progress');
    
    // 3. Добавляем комментарий
    await addComment(page, '', 'Заявка принята в работу');
    
    // 4. Решаем заявку
    await changeTicketStatus(page, '', 'resolved');
    
    // 5. Закрываем заявку
    await changeTicketStatus(page, '', 'closed');
    
    // Проверяем финальный статус
    await expect(page.locator('text=Закрыта')).toBeVisible();
  });

  test('Массовые операции с заявками', async ({ page }) => {
    await page.goto('/all-tickets');
    
    // Выбираем несколько заявок
    await page.check('input[type="checkbox"][name="ticket_ids[]"]');
    await page.check('input[type="checkbox"][name="ticket_ids[]"]:nth-of-type(2)');
    
    // Выполняем массовое действие
    await page.selectOption('select[name="bulk_action"]', 'assign');
    await page.selectOption('select[name="assigned_to_id"]', { index: 1 });
    await page.click('button[type="submit"]');
    
    // Проверяем, что действие выполнено
    await expect(page.locator('text=Заявки обновлены')).toBeVisible();
  });
});
