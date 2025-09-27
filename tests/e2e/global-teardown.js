/**
 * Глобальная очистка после тестов
 */

const { chromium } = require('@playwright/test');

async function globalTeardown(config) {
  console.log('🧹 Очистка глобального окружения после тестов...');
  
  const browser = await chromium.launch();
  const page = await browser.newPage();
  
  try {
    // Входим в систему как администратор
    await page.goto('/login');
    await page.fill('input[name="login"]', '+7 (999) 123-45-67');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    
    await page.waitForURL('**/');
    
    // Очищаем тестовые данные
    await cleanupTestData(page);
    
    console.log('✅ Очистка завершена');
    
  } catch (error) {
    console.error('❌ Ошибка при очистке:', error);
  } finally {
    await browser.close();
  }
}

async function cleanupTestData(page) {
  try {
    // Удаляем тестовые заявки
    await page.goto('/all-tickets');
    await page.waitForLoadState('networkidle');
    
    // Выбираем все заявки
    const selectAllCheckbox = page.locator('input[type="checkbox"][name="select_all"]');
    if (await selectAllCheckbox.isVisible()) {
      await selectAllCheckbox.check();
      
      // Выполняем массовое удаление
      await page.selectOption('select[name="bulk_action"]', 'delete');
      await page.click('button[type="submit"]');
      
      // Подтверждаем удаление
      await page.click('button[data-confirm="delete"]');
    }
    
    // Удаляем тестовое оборудование
    await page.goto('/equipment');
    await page.waitForLoadState('networkidle');
    
    const equipmentSelectAll = page.locator('input[type="checkbox"][name="select_all"]');
    if (await equipmentSelectAll.isVisible()) {
      await equipmentSelectAll.check();
      
      await page.selectOption('select[name="bulk_action"]', 'delete');
      await page.click('button[type="submit"]');
      await page.click('button[data-confirm="delete"]');
    }
    
    // Удаляем тестовые кабинеты
    await page.goto('/room');
    await page.waitForLoadState('networkidle');
    
    const roomSelectAll = page.locator('input[type="checkbox"][name="select_all"]');
    if (await roomSelectAll.isVisible()) {
      await roomSelectAll.check();
      
      await page.selectOption('select[name="bulk_action"]', 'delete');
      await page.click('button[type="submit"]');
      await page.click('button[data-confirm="delete"]');
    }
    
    // Удаляем тестовых пользователей (кроме админа)
    await page.goto('/user');
    await page.waitForLoadState('networkidle');
    
    // Удаляем пользователей по email
    const testEmails = ['master@test.com', 'technician@test.com', 'user@test.com'];
    
    for (const email of testEmails) {
      const userRow = page.locator(`tr:has-text("${email}")`);
      if (await userRow.isVisible()) {
        await userRow.locator('button[data-action="delete"]').click();
        await page.click('button[data-confirm="delete"]');
      }
    }
    
    // Удаляем тестовые статьи базы знаний
    await page.goto('/knowledge');
    await page.waitForLoadState('networkidle');
    
    const knowledgeSelectAll = page.locator('input[type="checkbox"][name="select_all"]');
    if (await knowledgeSelectAll.isVisible()) {
      await knowledgeSelectAll.check();
      
      await page.selectOption('select[name="bulk_action"]', 'delete');
      await page.click('button[type="submit"]');
      await page.click('button[data-confirm="delete"]');
    }
    
  } catch (error) {
    console.log('⚠️ Некоторые тестовые данные не были удалены:', error.message);
  }
}

module.exports = globalTeardown;
