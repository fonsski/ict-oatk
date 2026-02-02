// @ts-check
const { defineConfig, devices } = require('@playwright/test');


module.exports = defineConfig({
  testDir: './',
  
  fullyParallel: false,
  
  forbidOnly: !!process.env.CI,
  
  retries: process.env.CI ? 2 : 0,
  
  workers: 1,
  
  reporter: [
    ['html'],
    ['json', { outputFile: 'test-results.json' }],
    ['junit', { outputFile: 'test-results.xml' }]
  ],
  
  // globalSetup: require.resolve('./global-setup.js'),
  // globalTeardown: require.resolve('./global-teardown.js'),
  
  use: {
    
    baseURL: process.env.APP_URL || 'http://localhost',

    
    trace: 'on-first-retry',
    
    
    screenshot: 'only-on-failure',
    
    
    video: 'retain-on-failure',
    
    
    actionTimeout: 10000,
    
    
    navigationTimeout: 30000,
  },

  
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },

    {
      name: 'firefox',
      use: { ...devices['Desktop Firefox'] },
    },

    {
      name: 'webkit',
      use: { ...devices['Desktop Safari'] },
    },

    
    {
      name: 'Mobile Chrome',
      use: { ...devices['Pixel 5'] },
    },
    {
      name: 'Mobile Safari',
      use: { ...devices['iPhone 12'] },
    },
  ],

  
  webServer: {
    command: './vendor/bin/sail up -d',
    url: 'http://localhost',
    reuseExistingServer: !process.env.CI,
    timeout: 120 * 1000,
  },
});
