/**
 * Вспомогательные функции для работы с заявками
 */

import { testTickets } from '../fixtures/test-data.js';

/**
 * Создание новой заявки
 * @param {import('@playwright/test').Page} page
 * @param {string} ticketType - тип заявки (hardware, software, network)
 */
export async function createTicket(page, ticketType) {
  const ticket = testTickets[ticketType];
  
  await page.goto('/tickets/create');
  await page.waitForLoadState('networkidle');
  
  // Заполняем форму заявки
  await page.fill('input[name="title"]', ticket.title);
  await page.selectOption('select[name="category"]', ticket.category);
  await page.selectOption('select[name="priority"]', ticket.priority);
  await page.fill('textarea[name="description"]', ticket.description);
  
  // Если есть поля для заявителя
  if (await page.locator('input[name="reporter_name"]').isVisible()) {
    await page.fill('input[name="reporter_name"]', ticket.reporter_name);
    await page.fill('input[name="reporter_phone"]', ticket.reporter_phone);
  }
  
  // Выбираем кабинет, если доступно
  if (await page.locator('select[name="room_id"]').isVisible()) {
    await page.selectOption('select[name="room_id"]', { index: 1 }); // Выбираем первый доступный кабинет
  }
  
  // Отправляем форму
  await page.click('button[type="submit"]');
  
  // Ждем перенаправления на страницу заявки
  await page.waitForURL('**/tickets/*');
  await page.waitForLoadState('networkidle');
}

/**
 * Проверяем, что заявка создана
 * @param {import('@playwright/test').Page} page
 * @param {string} title
 */
export async function expectTicketCreated(page, title) {
  await page.waitForSelector(`text=${title}`);
}

/**
 * Изменяем статус заявки
 * @param {import('@playwright/test').Page} page
 * @param {string} ticketId
 * @param {string} status
 */
export async function changeTicketStatus(page, ticketId, status) {
  await page.goto(`/tickets/${ticketId}`);
  await page.waitForLoadState('networkidle');
  
  // Находим кнопку действий
  await page.click('[data-testid="ticket-actions"]');
  
  // Выбираем действие в зависимости от статуса
  switch (status) {
    case 'in_progress':
      await page.click('[data-testid="start-ticket"]');
      break;
    case 'resolved':
      await page.click('[data-testid="resolve-ticket"]');
      break;
    case 'closed':
      await page.click('[data-testid="close-ticket"]');
      break;
  }
  
  // Ждем обновления страницы
  await page.waitForLoadState('networkidle');
}

/**
 * Назначаем исполнителя заявке
 * @param {import('@playwright/test').Page} page
 * @param {string} ticketId
 * @param {string} assigneeName
 */
export async function assignTicket(page, ticketId, assigneeName) {
  await page.goto(`/tickets/${ticketId}`);
  await page.waitForLoadState('networkidle');
  
  // Находим форму назначения исполнителя
  await page.selectOption('select[name="assigned_to_id"]', { label: assigneeName });
  await page.click('button[type="submit"]');
  
  // Ждем обновления
  await page.waitForLoadState('networkidle');
}

/**
 * Добавляем комментарий к заявке
 * @param {import('@playwright/test').Page} page
 * @param {string} ticketId
 * @param {string} comment
 */
export async function addComment(page, ticketId, comment) {
  await page.goto(`/tickets/${ticketId}`);
  await page.waitForLoadState('networkidle');
  
  // Заполняем форму комментария
  await page.fill('textarea[name="content"]', comment);
  await page.click('button[type="submit"]');
  
  // Ждем обновления
  await page.waitForLoadState('networkidle');
}

/**
 * Фильтруем заявки
 * @param {import('@playwright/test').Page} page
 * @param {Object} filters
 */
export async function filterTickets(page, filters) {
  await page.goto('/all-tickets');
  await page.waitForLoadState('networkidle');
  
  // Применяем фильтры
  if (filters.status) {
    await page.selectOption('select[name="status"]', filters.status);
  }
  if (filters.priority) {
    await page.selectOption('select[name="priority"]', filters.priority);
  }
  if (filters.category) {
    await page.selectOption('select[name="category"]', filters.category);
  }
  if (filters.search) {
    await page.fill('input[name="search"]', filters.search);
  }
  
  // Применяем фильтры
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');
}
