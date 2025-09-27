/**
 * Тестовые данные для end-to-end тестов
 */

export const testUsers = {
  admin: {
    name: 'Администратор Тест',
    phone: '(999) 123-45-67',
    email: 'admin@test.com',
    password: 'password123',
    role: 'admin'
  },
  master: {
    name: 'Мастер Тест',
    phone: '(999) 123-45-68',
    email: 'master@test.com',
    password: 'password123',
    role: 'master'
  },
  technician: {
    name: 'Техник Тест',
    phone: '(999) 123-45-69',
    email: 'technician@test.com',
    password: 'password123',
    role: 'technician'
  },
  user: {
    name: 'Пользователь Тест',
    phone: '(999) 123-45-70',
    email: 'user@test.com',
    password: 'password123',
    role: 'user'
  }
};

export const testTickets = {
  hardware: {
    title: 'Не работает принтер',
    category: 'hardware',
    priority: 'medium',
    description: 'Принтер в кабинете 101 не печатает документы. При попытке печати выдает ошибку.',
    reporter_name: 'Иван Иванов',
    reporter_phone: '(999) 111-11-11'
  },
  software: {
    title: 'Проблема с программой',
    category: 'software',
    priority: 'high',
    description: 'Не запускается Microsoft Word. Выдает ошибку при запуске.',
    reporter_name: 'Петр Петров',
    reporter_phone: '(999) 222-22-22'
  },
  network: {
    title: 'Нет интернета',
    category: 'network',
    priority: 'urgent',
    description: 'В кабинете 205 пропал интернет. Не могу работать.',
    reporter_name: 'Сидор Сидоров',
    reporter_phone: '(999) 333-33-33'
  }
};

export const testEquipment = {
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
};

export const testRooms = {
  room101: {
    number: '101',
    name: 'Кабинет директора',
    type: 'office',
    building: 'Главный корпус',
    floor: '1'
  },
  room205: {
    number: '205',
    name: 'Компьютерный класс',
    type: 'classroom',
    building: 'Главный корпус',
    floor: '2'
  }
};

export const testKnowledge = {
  article: {
    title: 'Как настроить принтер',
    content: 'Пошаговая инструкция по настройке принтера...',
    category: 'hardware'
  }
};

export const testFAQ = {
  question: 'Как сбросить пароль?',
  answer: 'Для сброса пароля обратитесь к администратору системы.'
};
