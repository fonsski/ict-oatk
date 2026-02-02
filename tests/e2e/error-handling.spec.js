/**
 * Тесты обработки ошибок и граничных случаев
 */

import { test, expect } from '@playwright/test';
import { loginAs } from './helpers/auth-helpers.js';

test.describe('Обработка ошибок', () => {
  test('Ошибка 404 - несуществующая страница', async ({ page }) => {
    await page.goto('/nonexistent-page');
    await expect(page.locator('text=404')).toBeVisible();
    await expect(page.locator('text=Страница не найдена')).toBeVisible();
  });

  test('Ошибка 403 - доступ запрещен', async ({ page }) => {
    // Пытаемся зайти на админ-страницу без авторизации
    await page.goto('/user');
    await expect(page).toHaveURL(/.*login/);
  });

  test('Валидация формы входа - пустые поля', async ({ page }) => {
    await page.goto('/login');
    
    // Пытаемся отправить пустую форму
    await page.click('button[type="submit"]');
    
    // Должны появиться сообщения об ошибках валидации
    await expect(page.locator('text=Пожалуйста, введите номер телефона')).toBeVisible();
    await expect(page.locator('text=Пожалуйста, введите пароль')).toBeVisible();
  });

  test('Валидация формы регистрации - некорректные данные', async ({ page }) => {
    await page.goto('/register');
    
    // Заполняем некорректными данными
    await page.fill('input[name="name"]', '');
    await page.fill('input[name="phone"]', '123');
    await page.fill('input[name="password"]', '123');
    await page.fill('input[name="password_confirmation"]', '456');
    
    await page.click('button[type="submit"]');
    
    // Должны появиться сообщения об ошибках
    await expect(page.locator('text=Пожалуйста, укажите ваше имя')).toBeVisible();
    await expect(page.locator('text=Пароли не совпадают')).toBeVisible();
  });

  test('Ошибка при создании заявки - пустые обязательные поля', async ({ page }) => {
    await loginAs(page, 'user');
    await page.goto('/tickets/create');
    
    // Пытаемся создать заявку без заполнения обязательных полей
    await page.click('button[type="submit"]');
    
    // Должны появиться сообщения об ошибках
    await expect(page.locator('text=Поле "Название" обязательно')).toBeVisible();
    await expect(page.locator('text=Поле "Описание" обязательно')).toBeVisible();
  });

  test('Ошибка при создании оборудования - дублирование серийного номера', async ({ page }) => {
    await loginAs(page, 'admin');
    
    // Создаем первое оборудование
    await page.goto('/equipment/create');
    await page.fill('input[name="name"]', 'Тестовый компьютер');
    await page.fill('input[name="model"]', 'Test Model');
    await page.fill('input[name="serial_number"]', 'TEST123456');
    await page.fill('input[name="inventory_number"]', 'INV001');
    await page.selectOption('select[name="status"]', 'active');
    await page.selectOption('select[name="room_id"]', { index: 1 });
    await page.click('button[type="submit"]');
    
    // Пытаемся создать второе оборудование с тем же серийным номером
    await page.goto('/equipment/create');
    await page.fill('input[name="name"]', 'Другой компьютер');
    await page.fill('input[name="model"]', 'Another Model');
    await page.fill('input[name="serial_number"]', 'TEST123456'); // Дублируем серийный номер
    await page.fill('input[name="inventory_number"]', 'INV002');
    await page.selectOption('select[name="status"]', 'active');
    await page.selectOption('select[name="room_id"]', { index: 1 });
    await page.click('button[type="submit"]');
    
    // Должна появиться ошибка о дублировании
    await expect(page.locator('text=Серийный номер уже используется')).toBeVisible();
  });

  test('Ошибка при создании кабинета - дублирование номера', async ({ page }) => {
    await loginAs(page, 'admin');
    
    // Создаем первый кабинет
    await page.goto('/room/create');
    await page.fill('input[name="number"]', '101');
    await page.fill('input[name="name"]', 'Тестовый кабинет');
    await page.selectOption('select[name="type"]', 'office');
    await page.fill('input[name="building"]', 'Главный корпус');
    await page.fill('input[name="floor"]', '1');
    await page.click('button[type="submit"]');
    
    // Пытаемся создать второй кабинет с тем же номером
    await page.goto('/room/create');
    await page.fill('input[name="number"]', '101'); // Дублируем номер
    await page.fill('input[name="name"]', 'Другой кабинет');
    await page.selectOption('select[name="type"]', 'office');
    await page.fill('input[name="building"]', 'Главный корпус');
    await page.fill('input[name="floor"]', '1');
    await page.click('button[type="submit"]');
    
    // Должна появиться ошибка о дублировании
    await expect(page.locator('text=Кабинет с таким номером уже существует')).toBeVisible();
  });

  test('Ошибка при создании пользователя - дублирование email', async ({ page }) => {
    await loginAs(page, 'admin');
    
    // Создаем первого пользователя
    await page.goto('/user/create');
    await page.fill('input[name="name"]', 'Первый пользователь');
    await page.fill('input[name="phone"]', '(999) 111-11-11');
    await page.fill('input[name="email"]', 'duplicate@test.com');
    await page.fill('input[name="password"]', 'password123');
    await page.selectOption('select[name="role_id"]', { label: 'Пользователь' });
    await page.click('button[type="submit"]');
    
    // Пытаемся создать второго пользователя с тем же email
    await page.goto('/user/create');
    await page.fill('input[name="name"]', 'Второй пользователь');
    await page.fill('input[name="phone"]', '(999) 222-22-22');
    await page.fill('input[name="email"]', 'duplicate@test.com'); // Дублируем email
    await page.fill('input[name="password"]', 'password123');
    await page.selectOption('select[name="role_id"]', { label: 'Пользователь' });
    await page.click('button[type="submit"]');
    
    // Должна появиться ошибка о дублировании
    await expect(page.locator('text=Email уже используется')).toBeVisible();
  });

  test('Ошибка при удалении связанных данных', async ({ page }) => {
    await loginAs(page, 'admin');
    
    // Создаем кабинет с оборудованием
    await page.goto('/room/create');
    await page.fill('input[name="number"]', '999');
    await page.fill('input[name="name"]', 'Кабинет для удаления');
    await page.selectOption('select[name="type"]', 'office');
    await page.fill('input[name="building"]', 'Тестовый корпус');
    await page.fill('input[name="floor"]', '9');
    await page.click('button[type="submit"]');
    
    // Добавляем оборудование в кабинет
    await page.goto('/equipment/create');
    await page.fill('input[name="name"]', 'Оборудование в кабинете');
    await page.fill('input[name="model"]', 'Test Model');
    await page.fill('input[name="serial_number"]', 'TEST999');
    await page.fill('input[name="inventory_number"]', 'INV999');
    await page.selectOption('select[name="status"]', 'active');
    await page.selectOption('select[name="room_id"]', { label: '999 - Кабинет для удаления' });
    await page.click('button[type="submit"]');
    
    // Пытаемся удалить кабинет с оборудованием
    await page.goto('/room');
    await page.click('tr:has-text("999") button[data-action="delete"]');
    await page.click('button[data-confirm="delete"]');
    
    // Должна появиться ошибка о невозможности удаления
    await expect(page.locator('text=Невозможно удалить кабинет с оборудованием')).toBeVisible();
  });

  test('Ошибка при работе с несуществующими данными', async ({ page }) => {
    await loginAs(page, 'admin');
    
    // Пытаемся отредактировать несуществующую заявку
    await page.goto('/tickets/99999/edit');
    await expect(page.locator('text=Заявка не найдена')).toBeVisible();
    
    // Пытаемся отредактировать несуществующее оборудование
    await page.goto('/equipment/99999/edit');
    await expect(page.locator('text=Оборудование не найдено')).toBeVisible();
    
    // Пытаемся отредактировать несуществующий кабинет
    await page.goto('/room/99999/edit');
    await expect(page.locator('text=Кабинет не найден')).toBeVisible();
  });

  test('Ошибка при некорректных данных в формах', async ({ page }) => {
    await loginAs(page, 'admin');
    
    // Тестируем создание оборудования с некорректными данными
    await page.goto('/equipment/create');
    await page.fill('input[name="name"]', 'A'.repeat(300)); // Слишком длинное название
    await page.fill('input[name="model"]', 'B'.repeat(300)); // Слишком длинная модель
    await page.fill('input[name="serial_number"]', 'C'.repeat(100)); // Слишком длинный серийный номер
    await page.fill('input[name="inventory_number"]', 'D'.repeat(100)); // Слишком длинный инвентарный номер
    await page.click('button[type="submit"]');
    
    // Должны появиться ошибки валидации
    await expect(page.locator('text=Название не должно превышать 255 символов')).toBeVisible();
    await expect(page.locator('text=Модель не должна превышать 255 символов')).toBeVisible();
  });

  test('Ошибка при работе с неактивными пользователями', async ({ page }) => {
    await loginAs(page, 'admin');
    
    // Создаем неактивного пользователя
    await page.goto('/user/create');
    await page.fill('input[name="name"]', 'Неактивный пользователь');
    await page.fill('input[name="phone"]', '(999) 333-33-33');
    await page.fill('input[name="email"]', 'inactive@test.com');
    await page.fill('input[name="password"]', 'password123');
    await page.selectOption('select[name="role_id"]', { label: 'Пользователь' });
    await page.uncheck('input[name="is_active"]'); // Деактивируем пользователя
    await page.click('button[type="submit"]');
    
    // Пытаемся войти под неактивным пользователем
    await page.goto('/login');
    await page.fill('input[name="login"]', '(999) 333-33-33');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    
    // Должна появиться ошибка о неактивном пользователе
    await expect(page.locator('text=Ваш аккаунт деактивирован')).toBeVisible();
  });
});
