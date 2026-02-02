/**
 * Глобальная настройка для тестов
 */

const { chromium } = require('@playwright/test');

async function globalSetup(config) {
  console.log('🚀 Настройка глобального окружения для тестов...');
  
  // Создаем браузер для настройки
  const browser = await chromium.launch();
  const page = await browser.newPage();
  
  try {
    // Проверяем доступность приложения
    console.log('🔍 Проверка доступности приложения...');
    const baseURL = config.use?.baseURL || 'http://localhost';
    await page.goto(baseURL);
    await page.waitForLoadState('networkidle');
    
    // Проверяем, что приложение загрузилось
    const title = await page.title();
    if (!title.includes('ICT')) {
      throw new Error('Приложение не загрузилось корректно');
    }
    
    console.log('✅ Приложение доступно');
    
    // Создаем тестовых пользователей если их нет
    console.log('👥 Создание тестовых пользователей...');
    await createTestUsers(page);
    
    console.log('✅ Тестовые пользователи созданы');
    
  } catch (error) {
    console.error('❌ Ошибка при настройке:', error);
    throw error;
  } finally {
    await browser.close();
  }
  
  console.log('✅ Глобальная настройка завершена');
}

async function createTestUsers(page) {
  // Переходим на страницу регистрации
  await page.goto('/register');
  
  const testUsers = [
    {
      name: 'Администратор Тест',
      phone: '+7 (999) 123-45-67',
      email: 'admin@test.com',
      password: 'password123'
    },
    {
      name: 'Мастер Тест',
      phone: '+7 (999) 123-45-68',
      email: 'master@test.com',
      password: 'password123'
    },
    {
      name: 'Техник Тест',
      phone: '+7 (999) 123-45-69',
      email: 'technician@test.com',
      password: 'password123'
    },
    {
      name: 'Пользователь Тест',
      phone: '+7 (999) 123-45-70',
      email: 'user@test.com',
      password: 'password123'
    }
  ];
  
  for (const user of testUsers) {
    try {
      await page.fill('input[name="name"]', user.name);
      await page.fill('input[name="phone"]', user.phone);
      await page.fill('input[name="email"]', user.email);
      await page.fill('input[name="password"]', user.password);
      await page.fill('input[name="password_confirmation"]', user.password);
      
      await page.click('button[type="submit"]');
      
      // Ждем успешной регистрации
      await page.waitForURL('**/');
      
      // Выходим из системы
      await page.click('#user-menu-button');
      await page.click('a[href*="logout"]');
      
      // Ждем перенаправления на страницу входа
      await page.waitForURL('**/login');
      
    } catch (error) {
      console.log(`⚠️ Пользователь ${user.name} уже существует или ошибка при создании`);
    }
  }
}

module.exports = globalSetup;
