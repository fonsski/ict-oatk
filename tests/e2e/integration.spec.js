

import { test, expect } from '@playwright/test';
import { loginAs } from './helpers/auth-helpers.js';
import { createTicket, changeTicketStatus, assignTicket, addComment } from './helpers/ticket-helpers.js';
import { createEquipment } from './helpers/equipment-helpers.js';
import { createRoom } from './helpers/room-helpers.js';

test.describe('Полный цикл обработки заявки', () => {
  test('Сценарий: Пользователь создает заявку, техник обрабатывает', async ({ page }) => {
    // 1. Пользователь создает заявку
    await loginAs(page, 'user');
    await createTicket(page, 'hardware');
    const ticketUrl = page.url();
    
    // 2. Техник видит заявку в списке
    await loginAs(page, 'technician');
    await page.goto('/all-tickets');
    await expect(page.locator('text=Не работает принтер')).toBeVisible();
    
    // 3. Техник назначает себя исполнителем
    await page.goto(ticketUrl);
    await assignTicket(page, '', 'Техник Тест');
    
    // 4. Техник начинает работу
    await changeTicketStatus(page, '', 'in_progress');
    await expect(page.locator('text=В работе')).toBeVisible();
    
    // 5. Техник добавляет комментарий
    await addComment(page, '', 'Начинаю диагностику принтера');
    
    // 6. Техник решает проблему
    await changeTicketStatus(page, '', 'resolved');
    await expect(page.locator('text=Решена')).toBeVisible();
    
    // 7. Пользователь подтверждает решение
    await loginAs(page, 'user');
    await page.goto(ticketUrl);
    await changeTicketStatus(page, '', 'closed');
    await expect(page.locator('text=Закрыта')).toBeVisible();
  });

  test('Сценарий: Администратор управляет системой', async ({ page }) => {
    await loginAs(page, 'admin');
    
    // 1. Создаем кабинет
    await createRoom(page, 'room101');
    await expect(page.locator('text=101')).toBeVisible();
    
    // 2. Создаем оборудование в кабинете
    await createEquipment(page, 'computer');
    await expect(page.locator('text=Компьютер Dell OptiPlex')).toBeVisible();
    
    // 3. Создаем пользователя
    await page.goto('/user/create');
    await page.fill('input[name="name"]', 'Новый Пользователь');
    await page.fill('input[name="phone"]', '+7 (999) 123-45-99');
    await page.fill('input[name="email"]', 'newuser@test.com');
    await page.fill('input[name="password"]', 'password123');
    await page.selectOption('select[name="role_id"]', { label: 'Пользователь' });
    await page.click('button[type="submit"]');
    
    // 4. Проверяем, что пользователь создан
    await expect(page.locator('text=Новый Пользователь')).toBeVisible();
    
    // 5. Создаем статью в базе знаний
    await page.goto('/knowledge/create');
    await page.fill('input[name="title"]', 'Как настроить принтер');
    await page.fill('textarea[name="content"]', 'Пошаговая инструкция...');
    await page.selectOption('select[name="category_id"]', { index: 1 });
    await page.click('button[type="submit"]');
    
    // 6. Проверяем, что статья создана
    await expect(page.locator('text=Как настроить принтер')).toBeVisible();
  });

  test('Сценарий: Мастер управляет оборудованием', async ({ page }) => {
    await loginAs(page, 'master');
    
    // 1. Создаем кабинет
    await createRoom(page, 'room205');
    
    // 2. Создаем оборудование
    await createEquipment(page, 'printer');
    
    // 3. Перемещаем оборудование
    await page.goto('/equipment');
    await page.click('a[href*="/equipment/"]:first-of-type');
    await page.click('a[href*="/move"]');
    await page.selectOption('select[name="room_id"]', { index: 1 });
    await page.fill('textarea[name="comment"]', 'Перемещение в другой кабинет');
    await page.click('button[type="submit"]');
    
    // 4. Создаем запись обслуживания
    await page.goto('/equipment');
    await page.click('a[href*="/equipment/"]:first-of-type');
    await page.click('a[href*="/service/create"]');
    await page.fill('input[name="service_date"]', '2024-01-15');
    await page.fill('textarea[name="description"]', 'Плановое обслуживание');
    await page.fill('input[name="performed_by"]', 'Техник Иванов');
    await page.fill('input[name="cost"]', '5000');
    await page.click('button[type="submit"]');
    
    // 5. Проверяем, что запись создана
    await expect(page.locator('text=Плановое обслуживание')).toBeVisible();
  });

  test('Сценарий: Техник работает с заявками', async ({ page }) => {
    await loginAs(page, 'technician');
    
    // 1. Просматриваем все заявки
    await page.goto('/all-tickets');
    await expect(page.locator('text=Все заявки')).toBeVisible();
    
    // 2. Фильтруем заявки по статусу
    await page.selectOption('select[name="status"]', 'open');
    await page.click('button[type="submit"]');
    
    // 3. Выбираем заявку для работы
    await page.click('a[href*="/tickets/"]:first-of-type');
    
    // 4. Назначаем себя исполнителем
    await assignTicket(page, '', 'Техник Тест');
    
    // 5. Начинаем работу
    await changeTicketStatus(page, '', 'in_progress');
    
    // 6. Добавляем комментарий о прогрессе
    await addComment(page, '', 'Проблема диагностирована, начинаю ремонт');
    
    // 7. Завершаем работу
    await changeTicketStatus(page, '', 'resolved');
    
    // 8. Проверяем, что заявка решена
    await expect(page.locator('text=Решена')).toBeVisible();
  });

  test('Сценарий: Пользователь отслеживает свои заявки', async ({ page }) => {
    await loginAs(page, 'user');
    
    // 1. Создаем несколько заявок
    await createTicket(page, 'hardware');
    await createTicket(page, 'software');
    await createTicket(page, 'network');
    
    // 2. Просматриваем список своих заявок
    await page.goto('/tickets');
    await expect(page.locator('text=Мои заявки')).toBeVisible();
    
    // 3. Проверяем, что все заявки отображаются
    await expect(page.locator('text=Не работает принтер')).toBeVisible();
    await expect(page.locator('text=Проблема с программой')).toBeVisible();
    await expect(page.locator('text=Нет интернета')).toBeVisible();
    
    // 4. Фильтруем заявки по категории
    await page.selectOption('select[name="category"]', 'hardware');
    await page.click('button[type="submit"]');
    
    // 5. Проверяем, что отображается только заявка по оборудованию
    await expect(page.locator('text=Не работает принтер')).toBeVisible();
    await expect(page.locator('text=Проблема с программой')).not.toBeVisible();
    
    // 6. Сбрасываем фильтр
    await page.selectOption('select[name="category"]', '');
    await page.click('button[type="submit"]');
    
    // 7. Проверяем, что все заявки снова отображаются
    await expect(page.locator('text=Не работает принтер')).toBeVisible();
    await expect(page.locator('text=Проблема с программой')).toBeVisible();
    await expect(page.locator('text=Нет интернета')).toBeVisible();
  });
});

test.describe('Тестирование производительности', () => {
  test('Загрузка страниц должна быть быстрой', async ({ page }) => {
    await loginAs(page, 'admin');
    
    // Измеряем время загрузки главной страницы
    const startTime = Date.now();
    await page.goto('/');
    await page.waitForLoadState('networkidle');
    const loadTime = Date.now() - startTime;
    
    // Загрузка должна быть менее 3 секунд
    expect(loadTime).toBeLessThan(3000);
  });

  test('Поиск должен работать быстро', async ({ page }) => {
    await loginAs(page, 'admin');
    
    // Измеряем время поиска
    const startTime = Date.now();
    await page.goto('/all-tickets');
    await page.fill('input[name="search"]', 'тест');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    const searchTime = Date.now() - startTime;
    
    // Поиск должен быть менее 2 секунд
    expect(searchTime).toBeLessThan(2000);
  });
});

test.describe('Тестирование мобильной версии', () => {
  test('Мобильная навигация работает корректно', async ({ page }) => {
    // Устанавливаем мобильный viewport
    await page.setViewportSize({ width: 375, height: 667 });
    
    await loginAs(page, 'user');
    
    // Проверяем, что мобильное меню работает
    await page.click('#mobile-menu-button');
    await expect(page.locator('#mobile-menu')).toBeVisible();
    
    // Проверяем навигацию
    await page.click('a[href="/tickets/create"]');
    await expect(page.locator('text=Подать заявку')).toBeVisible();
  });

  test('Формы адаптируются под мобильные устройства', async ({ page }) => {
    // Устанавливаем мобильный viewport
    await page.setViewportSize({ width: 375, height: 667 });
    
    await loginAs(page, 'user');
    
    // Проверяем форму создания заявки
    await page.goto('/tickets/create');
    
    // Поля должны быть видимыми и кликабельными
    await expect(page.locator('input[name="title"]')).toBeVisible();
    await expect(page.locator('textarea[name="description"]')).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toBeVisible();
  });
});
