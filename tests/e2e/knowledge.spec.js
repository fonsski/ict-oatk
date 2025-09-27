/**
 * Тесты базы знаний
 */

import { test, expect } from '@playwright/test';
import { loginAs } from './helpers/auth-helpers.js';
import { testKnowledge } from './fixtures/test-data.js';

test.describe('База знаний', () => {
  test.beforeEach(async ({ page }) => {
    await loginAs(page, 'admin');
  });

  test('Просмотр базы знаний', async ({ page }) => {
    await page.goto('/knowledge');
    await expect(page).toHaveTitle(/База знаний/);
    await expect(page.locator('text=База знаний')).toBeVisible();
  });

  test('Создание новой статьи', async ({ page }) => {
    await page.goto('/knowledge/create');
    
    await page.fill('input[name="title"]', testKnowledge.article.title);
    await page.fill('textarea[name="content"]', testKnowledge.article.content);
    await page.selectOption('select[name="category_id"]', { index: 1 });
    
    await page.click('button[type="submit"]');
    
    await expect(page.locator(`text=${testKnowledge.article.title}`)).toBeVisible();
  });

  test('Просмотр статьи', async ({ page }) => {
    // Создаем статью
    await page.goto('/knowledge/create');
    await page.fill('input[name="title"]', testKnowledge.article.title);
    await page.fill('textarea[name="content"]', testKnowledge.article.content);
    await page.selectOption('select[name="category_id"]', { index: 1 });
    await page.click('button[type="submit"]');
    
    // Переходим к просмотру статьи
    await page.click('a[href*="/knowledge/"]');
    await expect(page.locator(`text=${testKnowledge.article.title}`)).toBeVisible();
    await expect(page.locator(`text=${testKnowledge.article.content}`)).toBeVisible();
  });

  test('Редактирование статьи', async ({ page }) => {
    // Создаем статью
    await page.goto('/knowledge/create');
    await page.fill('input[name="title"]', testKnowledge.article.title);
    await page.fill('textarea[name="content"]', testKnowledge.article.content);
    await page.selectOption('select[name="category_id"]', { index: 1 });
    await page.click('button[type="submit"]');
    
    // Переходим к редактированию
    await page.click('a[href*="/edit"]');
    
    const newTitle = 'Обновленная статья';
    await page.fill('input[name="title"]', newTitle);
    await page.click('button[type="submit"]');
    
    await expect(page.locator(`text=${newTitle}`)).toBeVisible();
  });

  test('Поиск в базе знаний', async ({ page }) => {
    await page.goto('/knowledge');
    
    // Ищем по ключевому слову
    await page.fill('input[name="search"]', 'принтер');
    await page.click('button[type="submit"]');
    
    await expect(page.locator('text=принтер')).toBeVisible();
  });

  test('Фильтрация по категориям', async ({ page }) => {
    await page.goto('/knowledge');
    
    // Фильтруем по категории
    await page.selectOption('select[name="category_id"]', { index: 1 });
    await page.click('button[type="submit"]');
  });

  test('Удаление статьи', async ({ page }) => {
    // Создаем статью
    await page.goto('/knowledge/create');
    await page.fill('input[name="title"]', 'Статья для удаления');
    await page.fill('textarea[name="content"]', 'Содержимое статьи');
    await page.selectOption('select[name="category_id"]', { index: 1 });
    await page.click('button[type="submit"]');
    
    // Удаляем статью
    await page.click('button[data-action="delete"]');
    await page.click('button[data-confirm="delete"]');
    
    await expect(page.locator('text=Статья удалена')).toBeVisible();
  });
});

test.describe('Управление категориями знаний', () => {
  test.beforeEach(async ({ page }) => {
    await loginAs(page, 'admin');
  });

  test('Создание категории знаний', async ({ page }) => {
    await page.goto('/knowledge/categories/create');
    
    await page.fill('input[name="name"]', 'Техническая поддержка');
    await page.fill('textarea[name="description"]', 'Категория для статей по технической поддержке');
    
    await page.click('button[type="submit"]');
    
    await expect(page.locator('text=Техническая поддержка')).toBeVisible();
  });

  test('Редактирование категории знаний', async ({ page }) => {
    await page.goto('/knowledge/categories');
    
    // Находим первую категорию и редактируем
    await page.click('a[href*="/edit"]:first-of-type');
    
    const newName = 'Обновленная категория';
    await page.fill('input[name="name"]', newName);
    await page.click('button[type="submit"]');
    
    await expect(page.locator(`text=${newName}`)).toBeVisible();
  });

  test('Удаление категории знаний', async ({ page }) => {
    await page.goto('/knowledge/categories');
    
    // Находим первую категорию и удаляем
    await page.click('button[data-action="delete"]:first-of-type');
    await page.click('button[data-confirm="delete"]');
    
    await expect(page.locator('text=Категория удалена')).toBeVisible();
  });
});

test.describe('Ограничения доступа к базе знаний', () => {
  test('Обычный пользователь не может управлять базой знаний', async ({ page }) => {
    await loginAs(page, 'user');
    
    // Пытаемся зайти на страницу базы знаний
    await page.goto('/knowledge');
    
    // Должны получить 403 ошибку или редирект
    const currentUrl = page.url();
    expect(currentUrl.includes('403') || currentUrl.includes('/login')).toBeTruthy();
  });

  test('Техник может просматривать базу знаний', async ({ page }) => {
    await loginAs(page, 'technician');
    
    await page.goto('/knowledge');
    await expect(page).toHaveTitle(/База знаний/);
  });

  test('Мастер может управлять базой знаний', async ({ page }) => {
    await loginAs(page, 'master');
    
    await page.goto('/knowledge');
    await expect(page).toHaveTitle(/База знаний/);
    
    // Должна быть кнопка создания
    await expect(page.locator('a[href*="/knowledge/create"]')).toBeVisible();
  });
});
