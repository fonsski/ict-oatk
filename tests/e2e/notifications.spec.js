/**
 * Тесты системы уведомлений
 */

import { test, expect } from '@playwright/test';
import { loginAs } from './helpers/auth-helpers.js';
import { createTicket, changeTicketStatus } from './helpers/ticket-helpers.js';

test.describe('Система уведомлений', () => {
  test('Просмотр уведомлений', async ({ page }) => {
    await loginAs(page, 'technician');
    
    // Открываем меню уведомлений
    await page.click('[data-testid="notifications-menu-button"]');
    
    // Проверяем, что меню открылось
    await expect(page.locator('[data-testid="notifications-dropdown"]')).toBeVisible();
    await expect(page.locator('text=Уведомления')).toBeVisible();
  });

  test('Отметка уведомлений как прочитанных', async ({ page }) => {
    await loginAs(page, 'technician');
    
    // Открываем меню уведомлений
    await page.click('[data-testid="notifications-menu-button"]');
    
    // Отмечаем все как прочитанные
    await page.click('[data-testid="mark-all-read"]');
    
    // Проверяем, что кнопка исчезла или изменилась
    await expect(page.locator('[data-testid="mark-all-read"]')).not.toBeVisible();
  });

  test('Уведомление о новой заявке', async ({ page }) => {
    // Создаем заявку от имени пользователя
    await loginAs(page, 'user');
    await createTicket(page, 'hardware');
    
    // Переключаемся на техника
    await loginAs(page, 'technician');
    
    // Проверяем, что появилось уведомление
    await page.click('[data-testid="notifications-menu-button"]');
    await expect(page.locator('text=Новая заявка')).toBeVisible();
  });

  test('Уведомление об изменении статуса заявки', async ({ page }) => {
    // Создаем заявку
    await loginAs(page, 'user');
    await createTicket(page, 'software');
    const ticketUrl = page.url();
    
    // Переключаемся на техника
    await loginAs(page, 'technician');
    await page.goto(ticketUrl);
    
    // Изменяем статус
    await changeTicketStatus(page, '', 'in_progress');
    
    // Переключаемся обратно на пользователя
    await loginAs(page, 'user');
    
    // Проверяем уведомление
    await page.click('[data-testid="notifications-menu-button"]');
    await expect(page.locator('text=Статус заявки изменен')).toBeVisible();
  });

  test('Уведомление о назначении исполнителя', async ({ page }) => {
    // Создаем заявку
    await loginAs(page, 'user');
    await createTicket(page, 'network');
    const ticketUrl = page.url();
    
    // Переключаемся на техника
    await loginAs(page, 'technician');
    await page.goto(ticketUrl);
    
    // Назначаем исполнителя
    await page.selectOption('select[name="assigned_to_id"]', { index: 1 });
    await page.click('button[type="submit"]');
    
    // Переключаемся обратно на пользователя
    await loginAs(page, 'user');
    
    // Проверяем уведомление
    await page.click('[data-testid="notifications-menu-button"]');
    await expect(page.locator('text=Назначен исполнитель')).toBeVisible();
  });

  test('Уведомление о добавлении комментария', async ({ page }) => {
    // Создаем заявку
    await loginAs(page, 'user');
    await createTicket(page, 'hardware');
    const ticketUrl = page.url();
    
    // Переключаемся на техника
    await loginAs(page, 'technician');
    await page.goto(ticketUrl);
    
    // Добавляем комментарий
    await page.fill('textarea[name="content"]', 'Комментарий к заявке');
    await page.click('button[type="submit"]');
    
    // Переключаемся обратно на пользователя
    await loginAs(page, 'user');
    
    // Проверяем уведомление
    await page.click('[data-testid="notifications-menu-button"]');
    await expect(page.locator('text=Добавлен комментарий')).toBeVisible();
  });

  test('Счетчик непрочитанных уведомлений', async ({ page }) => {
    await loginAs(page, 'technician');
    
    // Создаем несколько заявок
    await loginAs(page, 'user');
    await createTicket(page, 'hardware');
    await createTicket(page, 'software');
    await createTicket(page, 'network');
    
    // Переключаемся на техника
    await loginAs(page, 'technician');
    
    // Проверяем счетчик
    const badge = page.locator('[data-testid="notification-badge"]');
    await expect(badge).toBeVisible();
    await expect(badge).toContainText('3');
  });

  test('Автоматическое обновление уведомлений', async ({ page }) => {
    await loginAs(page, 'technician');
    
    // Открываем меню уведомлений
    await page.click('[data-testid="notifications-menu-button"]');
    
    // Создаем заявку в другом окне (симуляция)
    await loginAs(page, 'user');
    await createTicket(page, 'hardware');
    
    // Переключаемся обратно на техника
    await loginAs(page, 'technician');
    
    // Проверяем, что уведомления обновились
    await page.click('[data-testid="notifications-menu-button"]');
    await expect(page.locator('text=Новая заявка')).toBeVisible();
  });
});

test.describe('Настройки уведомлений', () => {
  test('Настройка уведомлений по email', async ({ page }) => {
    await loginAs(page, 'user');
    
    // Переходим в настройки профиля
    await page.click('[data-testid="user-menu"]');
    await page.click('a[href*="/profile"]');
    
    // Включаем уведомления по email
    await page.check('input[name="email_notifications"]');
    await page.click('button[type="submit"]');
    
    await expect(page.locator('text=Настройки сохранены')).toBeVisible();
  });

  test('Настройка уведомлений по Telegram', async ({ page }) => {
    await loginAs(page, 'user');
    
    // Переходим в настройки профиля
    await page.click('[data-testid="user-menu"]');
    await page.click('a[href*="/profile"]');
    
    // Включаем уведомления по Telegram
    await page.check('input[name="telegram_notifications"]');
    await page.fill('input[name="telegram_id"]', '123456789');
    await page.click('button[type="submit"]');
    
    await expect(page.locator('text=Настройки сохранены')).toBeVisible();
  });
});
