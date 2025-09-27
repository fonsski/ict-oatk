/**
 * Настройки для разных тестовых окружений
 */

export const testEnvironments = {
  development: {
    baseURL: 'http://localhost',
    timeout: 30000,
    retries: 0,
    workers: 1,
    headed: true,
    parallel: false
  },
  
  staging: {
    baseURL: 'http://staging.ict.local',
    timeout: 60000,
    retries: 2,
    workers: 2,
    headed: false,
    parallel: true
  },
  
  production: {
    baseURL: 'https://ict.example.com',
    timeout: 120000,
    retries: 3,
    workers: 4,
    headed: false,
    parallel: true
  },
  
  ci: {
    baseURL: process.env.APP_URL || 'http://localhost',
    timeout: 60000,
    retries: 2,
    workers: 1,
    headed: false,
    parallel: false
  }
};

export const testSuites = {
  smoke: [
    'auth.spec.js',
    'tickets.spec.js',
    'integration.spec.js'
  ],
  
  critical: [
    'auth.spec.js',
    'security.spec.js',
    'error-handling.spec.js'
  ],
  
  full: [
    'auth.spec.js',
    'tickets.spec.js',
    'equipment.spec.js',
    'rooms.spec.js',
    'users.spec.js',
    'knowledge.spec.js',
    'notifications.spec.js',
    'integration.spec.js',
    'error-handling.spec.js',
    'performance.spec.js',
    'security.spec.js'
  ],
  
  regression: [
    'auth.spec.js',
    'tickets.spec.js',
    'equipment.spec.js',
    'rooms.spec.js',
    'users.spec.js',
    'knowledge.spec.js',
    'notifications.spec.js',
    'integration.spec.js'
  ]
};

export const testRoles = {
  admin: {
    name: 'Администратор Тест',
    phone: '(999) 123-45-67',
    email: 'admin@test.com',
    password: 'password123',
    permissions: ['all']
  },
  
  master: {
    name: 'Мастер Тест',
    phone: '(999) 123-45-68',
    email: 'master@test.com',
    password: 'password123',
    permissions: ['equipment', 'rooms', 'users', 'tickets']
  },
  
  technician: {
    name: 'Техник Тест',
    phone: '(999) 123-45-69',
    email: 'technician@test.com',
    password: 'password123',
    permissions: ['tickets', 'knowledge']
  },
  
  user: {
    name: 'Пользователь Тест',
    phone: '(999) 123-45-70',
    email: 'user@test.com',
    password: 'password123',
    permissions: ['tickets_own']
  }
};

export const testData = {
  tickets: {
    hardware: {
      title: 'Не работает принтер',
      category: 'hardware',
      priority: 'medium',
      description: 'Принтер в кабинете 101 не печатает документы.',
      reporter_name: 'Иван Иванов',
      reporter_phone: '(999) 111-11-11'
    },
    
    software: {
      title: 'Проблема с программой',
      category: 'software',
      priority: 'high',
      description: 'Не запускается Microsoft Word.',
      reporter_name: 'Петр Петров',
      reporter_phone: '(999) 222-22-22'
    },
    
    network: {
      title: 'Нет интернета',
      category: 'network',
      priority: 'urgent',
      description: 'В кабинете 205 пропал интернет.',
      reporter_name: 'Сидор Сидоров',
      reporter_phone: '(999) 333-33-33'
    }
  },
  
  equipment: {
    computer: {
      name: 'Компьютер Dell OptiPlex',
      model: 'OptiPlex 7090',
      serial_number: 'DL123456789',
      inventory_number: 'INV001',
      status: 'active'
    },
    
    printer: {
      name: 'Принтер HP LaserJet',
      model: 'LaserJet Pro M404n',
      serial_number: 'HP987654321',
      inventory_number: 'INV002',
      status: 'active'
    }
  },
  
  rooms: {
    office: {
      number: '101',
      name: 'Кабинет директора',
      type: 'office',
      building: 'Главный корпус',
      floor: '1'
    },
    
    classroom: {
      number: '205',
      name: 'Компьютерный класс',
      type: 'classroom',
      building: 'Главный корпус',
      floor: '2'
    }
  }
};

export const performanceThresholds = {
  pageLoad: 2000,      // 2 секунды
  action: 1000,        // 1 секунда
  api: 500,           // 0.5 секунды
  search: 1500,       // 1.5 секунды
  create: 3000,       // 3 секунды
  update: 2000,       // 2 секунды
  delete: 1000        // 1 секунда
};

export const securityTests = {
  sqlInjection: [
    "'; DROP TABLE users; --",
    "' OR '1'='1",
    "'; INSERT INTO users VALUES ('hacker', 'password'); --"
  ],
  
  xss: [
    '<script>alert("XSS")</script>',
    '<img src="x" onerror="alert(\'XSS\')">',
    '<svg onload="alert(\'XSS\')">',
    'javascript:alert("XSS")'
  ],
  
  csrf: [
    'POST /tickets',
    'PUT /api/user/1',
    'DELETE /api/equipment/1'
  ]
};
