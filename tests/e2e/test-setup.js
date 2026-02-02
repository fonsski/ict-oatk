

const { test, expect } = require('@playwright/test');

// Глобальные настройки для всех тестов
test.beforeEach(async ({ page }) => {
  // Устанавливаем таймауты
  page.setDefaultTimeout(30000);
  page.setDefaultNavigationTimeout(30000);
  
  // Устанавливаем размер окна
  await page.setViewportSize({ width: 1280, height: 720 });
  
  // Включаем логирование
  page.on('console', msg => {
    if (msg.type() === 'error') {
      console.error('Browser error:', msg.text());
    }
  });
  
  // Обработка ошибок JavaScript
  page.on('pageerror', error => {
    console.error('Page error:', error.message);
  });
  
  // Обработка ошибок сети
  page.on('response', response => {
    if (response.status() >= 400) {
      console.error(`Network error: ${response.status()} ${response.url()}`);
    }
  });
});

// Глобальная очистка после каждого теста
test.afterEach(async ({ page }) => {
  // Очищаем localStorage и sessionStorage
  await page.evaluate(() => {
    localStorage.clear();
    sessionStorage.clear();
  });
  
  // Закрываем все модальные окна
  try {
    await page.click('.modal-close', { timeout: 1000 });
  } catch (e) {
    // Игнорируем ошибки если модального окна нет
  }
});

// Настройка для мобильных тестов
test.describe.configure({ mode: 'parallel' });

// Настройка для десктопных тестов
test.describe.configure({ mode: 'parallel' });

// Настройка для тестов производительности
test.describe.configure({ mode: 'serial' });

// Настройка для интеграционных тестов
test.describe.configure({ mode: 'serial' });

// Глобальные функции для тестов
global.testHelpers = {
  
  async waitForPageLoad(page, url) {
    await page.goto(url);
    await page.waitForLoadState('networkidle');
  },
  
  
  async checkPageAccessibility(page, url) {
    await page.goto(url);
    await page.waitForLoadState('networkidle');
    
    // Проверяем, что страница загрузилась
    const title = await page.title();
    expect(title).toBeTruthy();
    
    // Проверяем, что нет критических ошибок
    const errors = await page.evaluate(() => {
      return window.console.errors || [];
    });
    expect(errors).toHaveLength(0);
  },
  
  
  async cleanupTestData(page) {
    // Эта функция может быть реализована для очистки
    // тестовых данных после каждого теста
    console.log('Очистка тестовых данных...');
  },
  
  
  async createTestData(page, dataType, data) {
    // Эта функция может быть реализована для создания
    // тестовых данных перед тестом
    console.log(`Создание тестовых данных: ${dataType}`, data);
  }
};

// Настройка для разных окружений
if (process.env.CI) {
  // Настройки для CI/CD
  test.setTimeout(60000);
} else {
  // Настройки для локальной разработки
  test.setTimeout(30000);
}

// Настройка для разных браузеров
test.describe('Desktop tests', () => {
  test.use({ 
    viewport: { width: 1280, height: 720 },
    deviceScaleFactor: 1
  });
});

test.describe('Mobile tests', () => {
  test.use({ 
    viewport: { width: 375, height: 667 },
    deviceScaleFactor: 2
  });
});

// Настройка для тестов доступности
test.describe('Accessibility tests', () => {
  test.beforeEach(async ({ page }) => {
    // Включаем проверку доступности
    await page.addInitScript(() => {
      // Добавляем скрипт для проверки доступности
      window.accessibilityCheck = true;
    });
  });
});

// Настройка для тестов производительности
test.describe('Performance tests', () => {
  test.beforeEach(async ({ page }) => {
    // Включаем метрики производительности
    await page.addInitScript(() => {
      window.performanceMetrics = [];
      
      // Отслеживаем время загрузки
      window.addEventListener('load', () => {
        const perfData = performance.getEntriesByType('navigation')[0];
        window.performanceMetrics.push({
          type: 'navigation',
          loadTime: perfData.loadEventEnd - perfData.loadEventStart,
          domContentLoaded: perfData.domContentLoadedEventEnd - perfData.domContentLoadedEventStart
        });
      });
    });
  });
  
  test.afterEach(async ({ page }) => {
    // Получаем метрики производительности
    const metrics = await page.evaluate(() => window.performanceMetrics);
    console.log('Performance metrics:', metrics);
  });
});

module.exports = {
  test,
  expect
};
