/**
 * Тесты безопасности системы
 */

import { test, expect } from '@playwright/test';
import { loginAs } from './helpers/auth-helpers.js';

test.describe('Тесты безопасности', () => {
  test('Защита от SQL-инъекций в поиске', async ({ page }) => {
    await loginAs(page, 'technician');
    
    // Пытаемся выполнить SQL-инъекцию через поиск
    await page.goto('/all-tickets');
    await page.fill('input[name="search"]', "'; DROP TABLE users; --");
    await page.click('button[type="submit"]');
    
    // Система должна обработать запрос безопасно
    await page.waitForLoadState('networkidle');
    
    // Проверяем, что система не сломалась
    await expect(page.locator('table')).toBeVisible();
    
    // Пытаемся выполнить другую SQL-инъекцию
    await page.fill('input[name="search"]', "' OR '1'='1");
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    
    // Система должна остаться работоспособной
    await expect(page.locator('table')).toBeVisible();
  });

  test('Защита от XSS атак', async ({ page }) => {
    await loginAs(page, 'user');
    
    // Пытаемся внедрить XSS через название заявки
    await page.goto('/tickets/create');
    await page.fill('input[name="title"]', '<script>alert("XSS")</script>');
    await page.fill('textarea[name="description"]', '<img src="x" onerror="alert(\'XSS\')">');
    await page.selectOption('select[name="category"]', 'hardware');
    await page.selectOption('select[name="priority"]', 'medium');
    await page.click('button[type="submit"]');
    
    // Проверяем, что скрипты не выполнились
    await page.waitForURL('**/tickets/*');
    
    // Название должно быть экранировано
    await expect(page.locator('text=<script>alert("XSS")</script>')).not.toBeVisible();
    await expect(page.locator('text=alert("XSS")')).not.toBeVisible();
  });

  test('Защита от CSRF атак', async ({ page }) => {
    await loginAs(page, 'user');
    
    // Получаем CSRF токен
    await page.goto('/tickets/create');
    const csrfToken = await page.locator('input[name="_token"]').inputValue();
    
    // Пытаемся отправить запрос без CSRF токена
    const response = await page.request.post('/tickets', {
      data: {
        title: 'CSRF Test',
        description: 'Test description',
        category: 'hardware',
        priority: 'medium'
        // Намеренно не включаем _token
      }
    });
    
    // Запрос должен быть отклонен
    expect(response.status()).toBe(419); // CSRF token mismatch
  });

  test('Защита от брутфорс атак', async ({ page }) => {
    // Пытаемся войти с неверным паролем много раз
    for (let i = 0; i < 6; i++) {
      await page.goto('/login');
      await page.fill('input[name="login"]', '(999) 123-45-67');
      await page.fill('input[name="password"]', 'wrongpassword');
      await page.click('button[type="submit"]');
      
      if (i < 5) {
        // Первые 5 попыток должны показывать ошибку
        await expect(page.locator('text=Неверные учетные данные')).toBeVisible();
      } else {
        // После 5 попыток должна появиться блокировка
        await expect(page.locator('text=Слишком много попыток входа')).toBeVisible();
      }
    }
  });

  test('Защита от доступа к чужим данным', async ({ page }) => {
    // Создаем заявку от имени пользователя
    await loginAs(page, 'user');
    await page.goto('/tickets/create');
    await page.fill('input[name="title"]', 'Приватная заявка');
    await page.fill('textarea[name="description"]', 'Описание приватной заявки');
    await page.selectOption('select[name="category"]', 'hardware');
    await page.selectOption('select[name="priority"]', 'medium');
    await page.click('button[type="submit"]');
    
    const ticketUrl = page.url();
    const ticketId = ticketUrl.split('/').pop();
    
    // Выходим из системы
    await page.click('#user-menu-button');
    await page.click('a[href*="logout"]');
    
    // Пытаемся войти под другим пользователем и получить доступ к заявке
    await loginAs(page, 'user');
    await page.goto(ticketUrl);
    
    // Должны получить доступ к своей заявке
    await expect(page.locator('text=Приватная заявка')).toBeVisible();
    
    // Выходим и входим под другим пользователем
    await page.click('#user-menu-button');
    await page.click('a[href*="logout"]');
    
    // Создаем нового пользователя
    await page.goto('/register');
    await page.fill('input[name="name"]', 'Другой пользователь');
    await page.fill('input[name="phone"]', '(999) 999-99-99');
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="password_confirmation"]', 'password123');
    await page.click('button[type="submit"]');
    
    // Пытаемся получить доступ к чужой заявке
    await page.goto(ticketUrl);
    
    // Должны получить ошибку доступа
    await expect(page.locator('text=Доступ запрещен')).toBeVisible();
  });

  test('Защита от несанкционированного доступа к API', async ({ page }) => {
    // Пытаемся получить доступ к API без авторизации
    const response = await page.request.get('/api/notifications');
    expect(response.status()).toBe(401);
    
    // Пытаемся отправить POST запрос без авторизации
    const postResponse = await page.request.post('/api/notifications', {
      data: { message: 'Test' }
    });
    expect(postResponse.status()).toBe(401);
  });

  test('Защита от несанкционированного доступа к админ-функциям', async ({ page }) => {
    await loginAs(page, 'user');
    
    // Пытаемся получить доступ к админ-функциям
    await page.goto('/user');
    await expect(page).toHaveURL(/.*403/);
    
    await page.goto('/equipment');
    await expect(page).toHaveURL(/.*403/);
    
    await page.goto('/room');
    await expect(page).toHaveURL(/.*403/);
  });

  test('Защита от несанкционированного изменения данных', async ({ page }) => {
    await loginAs(page, 'user');
    
    // Пытаемся изменить данные через прямые запросы
    const response = await page.request.put('/api/user/1', {
      data: { role_id: 1 } // Пытаемся стать админом
    });
    expect(response.status()).toBe(403);
    
    const response2 = await page.request.delete('/api/equipment/1');
    expect(response2.status()).toBe(403);
  });

  test('Защита от несанкционированного доступа к файлам', async ({ page }) => {
    // Пытаемся получить доступ к системным файлам
    const response = await page.request.get('/.env');
    expect(response.status()).toBe(404);
    
    const response2 = await page.request.get('/config/database.php');
    expect(response2.status()).toBe(404);
    
    const response3 = await page.request.get('/storage/logs/laravel.log');
    expect(response3.status()).toBe(404);
  });

  test('Защита от несанкционированного доступа к заявкам', async ({ page }) => {
    await loginAs(page, 'user');
    
    // Создаем заявку
    await page.goto('/tickets/create');
    await page.fill('input[name="title"]', 'Тестовая заявка');
    await page.fill('textarea[name="description"]', 'Описание');
    await page.selectOption('select[name="category"]', 'hardware');
    await page.selectOption('select[name="priority"]', 'medium');
    await page.click('button[type="submit"]');
    
    const ticketUrl = page.url();
    
    // Выходим из системы
    await page.click('#user-menu-button');
    await page.click('a[href*="logout"]');
    
    // Пытаемся получить доступ к заявке без авторизации
    await page.goto(ticketUrl);
    await expect(page).toHaveURL(/.*login/);
  });

  test('Защита от несанкционированного доступа к уведомлениям', async ({ page }) => {
    await loginAs(page, 'user');
    
    // Пытаемся получить доступ к уведомлениям (только для техников и выше)
    await page.goto('/api/notifications');
    const response = await page.request.get('/api/notifications');
    expect(response.status()).toBe(403);
  });

  test('Защита от несанкционированного доступа к базе знаний', async ({ page }) => {
    await loginAs(page, 'user');
    
    // Пытаемся получить доступ к базе знаний (только для техников и выше)
    await page.goto('/knowledge');
    await expect(page).toHaveURL(/.*403/);
  });

  test('Защита от несанкционированного доступа к управлению заявками', async ({ page }) => {
    await loginAs(page, 'user');
    
    // Пытаемся получить доступ к управлению заявками (только для техников и выше)
    await page.goto('/all-tickets');
    await expect(page).toHaveURL(/.*403/);
  });

  test('Защита от несанкционированного доступа к управлению оборудованием', async ({ page }) => {
    await loginAs(page, 'user');
    
    // Пытаемся получить доступ к управлению оборудованием (только для мастеров и выше)
    await page.goto('/equipment');
    await expect(page).toHaveURL(/.*403/);
  });

  test('Защита от несанкционированного доступа к управлению пользователями', async ({ page }) => {
    await loginAs(page, 'user');
    
    // Пытаемся получить доступ к управлению пользователями (только для мастеров и выше)
    await page.goto('/user');
    await expect(page).toHaveURL(/.*403/);
  });

  test('Защита от несанкционированного доступа к управлению кабинетами', async ({ page }) => {
    await loginAs(page, 'user');
    
    // Пытаемся получить доступ к управлению кабинетами (только для мастеров и выше)
    await page.goto('/room');
    await expect(page).toHaveURL(/.*403/);
  });
});
