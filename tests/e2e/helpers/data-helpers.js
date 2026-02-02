/**
 * Вспомогательные функции для работы с тестовыми данными
 */

import { testUsers, testTickets, testEquipment, testRooms } from '../fixtures/test-data.js';

/**
 * Получение тестового пользователя по роли
 * @param {string} role
 * @returns {Object}
 */
export function getTestUser(role) {
  return testUsers[role];
}

/**
 * Получение тестовой заявки по типу
 * @param {string} type
 * @returns {Object}
 */
export function getTestTicket(type) {
  return testTickets[type];
}

/**
 * Получение тестового оборудования по типу
 * @param {string} type
 * @returns {Object}
 */
export function getTestEquipment(type) {
  return testEquipment[type];
}

/**
 * Получение тестового кабинета по типу
 * @param {string} type
 * @returns {Object}
 */
export function getTestRoom(type) {
  return testRooms[type];
}

/**
 * Генерация случайного номера телефона
 * @returns {string}
 */
export function generateRandomPhone() {
  const random = Math.floor(Math.random() * 10000000000);
  return `(999) ${random.toString().slice(0, 3)}-${random.toString().slice(3, 5)}-${random.toString().slice(5, 7)}`;
}

/**
 * Генерация случайного email
 * @returns {string}
 */
export function generateRandomEmail() {
  const random = Math.floor(Math.random() * 1000000);
  return `test${random}@example.com`;
}

/**
 * Генерация случайного имени
 * @returns {string}
 */
export function generateRandomName() {
  const names = ['Иван', 'Петр', 'Сидор', 'Алексей', 'Дмитрий', 'Сергей', 'Андрей', 'Николай'];
  const surnames = ['Иванов', 'Петров', 'Сидоров', 'Алексеев', 'Дмитриев', 'Сергеев', 'Андреев', 'Николаев'];
  
  const randomName = names[Math.floor(Math.random() * names.length)];
  const randomSurname = surnames[Math.floor(Math.random() * surnames.length)];
  
  return `${randomName} ${randomSurname}`;
}

/**
 * Генерация случайного номера кабинета
 * @returns {string}
 */
export function generateRandomRoomNumber() {
  const floor = Math.floor(Math.random() * 5) + 1;
  const room = Math.floor(Math.random() * 20) + 1;
  return `${floor}${room.toString().padStart(2, '0')}`;
}

/**
 * Генерация случайного серийного номера
 * @returns {string}
 */
export function generateRandomSerialNumber() {
  const prefix = ['DL', 'HP', 'LG', 'SN', 'AS'];
  const randomPrefix = prefix[Math.floor(Math.random() * prefix.length)];
  const randomNumber = Math.floor(Math.random() * 1000000000);
  return `${randomPrefix}${randomNumber}`;
}

/**
 * Генерация случайного инвентарного номера
 * @returns {string}
 */
export function generateRandomInventoryNumber() {
  const random = Math.floor(Math.random() * 100000);
  return `INV${random.toString().padStart(5, '0')}`;
}

/**
 * Создание тестовых данных для заявки
 * @param {Object} overrides
 * @returns {Object}
 */
export function createTestTicketData(overrides = {}) {
  const baseTicket = {
    title: `Тестовая заявка ${Date.now()}`,
    category: 'hardware',
    priority: 'medium',
    description: 'Описание тестовой заявки',
    reporter_name: generateRandomName(),
    reporter_phone: generateRandomPhone()
  };
  
  return { ...baseTicket, ...overrides };
}

/**
 * Создание тестовых данных для оборудования
 * @param {Object} overrides
 * @returns {Object}
 */
export function createTestEquipmentData(overrides = {}) {
  const baseEquipment = {
    name: `Тестовое оборудование ${Date.now()}`,
    model: 'Test Model',
    serial_number: generateRandomSerialNumber(),
    inventory_number: generateRandomInventoryNumber(),
    status: 'active'
  };
  
  return { ...baseEquipment, ...overrides };
}

/**
 * Создание тестовых данных для кабинета
 * @param {Object} overrides
 * @returns {Object}
 */
export function createTestRoomData(overrides = {}) {
  const baseRoom = {
    number: generateRandomRoomNumber(),
    name: `Тестовый кабинет ${Date.now()}`,
    type: 'office',
    building: 'Тестовое здание',
    floor: '1'
  };
  
  return { ...baseRoom, ...overrides };
}

/**
 * Создание тестовых данных для пользователя
 * @param {Object} overrides
 * @returns {Object}
 */
export function createTestUserData(overrides = {}) {
  const baseUser = {
    name: generateRandomName(),
    phone: generateRandomPhone(),
    email: generateRandomEmail(),
    password: 'password123',
    role: 'user'
  };
  
  return { ...baseUser, ...overrides };
}

/**
 * Очистка тестовых данных
 * @param {import('@playwright/test').Page} page
 */
export async function cleanupTestData(page) {
  // Эта функция может быть реализована для очистки тестовых данных
  // после выполнения тестов, если это необходимо
  console.log('Очистка тестовых данных...');
}

/**
 * Создание тестовых данных в базе данных
 * @param {import('@playwright/test').Page} page
 * @param {string} entityType
 * @param {Object} data
 */
export async function createTestDataInDB(page, entityType, data) {
  // Эта функция может быть реализована для создания тестовых данных
  // непосредственно в базе данных через API или другие методы
  console.log(`Создание тестовых данных: ${entityType}`, data);
}

/**
 * Удаление тестовых данных из базы данных
 * @param {import('@playwright/test').Page} page
 * @param {string} entityType
 * @param {string} identifier
 */
export async function deleteTestDataFromDB(page, entityType, identifier) {
  // Эта функция может быть реализована для удаления тестовых данных
  // из базы данных после выполнения тестов
  console.log(`Удаление тестовых данных: ${entityType} - ${identifier}`);
}

/**
 * Получение ID созданной сущности
 * @param {import('@playwright/test').Page} page
 * @returns {string}
 */
export async function getCreatedEntityId(page) {
  const url = page.url();
  const match = url.match(/\/(\d+)(?:\/|$)/);
  return match ? match[1] : null;
}

/**
 * Ожидание создания сущности
 * @param {import('@playwright/test').Page} page
 * @param {string} expectedText
 */
export async function waitForEntityCreation(page, expectedText) {
  await page.waitForSelector(`text=${expectedText}`);
}

/**
 * Проверка создания сущности
 * @param {import('@playwright/test').Page} page
 * @param {string} expectedText
 */
export async function expectEntityCreated(page, expectedText) {
  await page.waitForSelector(`text=${expectedText}`);
}

/**
 * Получение случайного значения из массива
 * @param {Array} array
 * @returns {*}
 */
export function getRandomValue(array) {
  return array[Math.floor(Math.random() * array.length)];
}

/**
 * Получение случайного статуса заявки
 * @returns {string}
 */
export function getRandomTicketStatus() {
  const statuses = ['open', 'in_progress', 'resolved', 'closed'];
  return getRandomValue(statuses);
}

/**
 * Получение случайного приоритета заявки
 * @returns {string}
 */
export function getRandomTicketPriority() {
  const priorities = ['low', 'medium', 'high', 'urgent'];
  return getRandomValue(priorities);
}

/**
 * Получение случайной категории заявки
 * @returns {string}
 */
export function getRandomTicketCategory() {
  const categories = ['hardware', 'software', 'network', 'account', 'other'];
  return getRandomValue(categories);
}

/**
 * Получение случайного статуса оборудования
 * @returns {string}
 */
export function getRandomEquipmentStatus() {
  const statuses = ['active', 'inactive', 'maintenance', 'broken'];
  return getRandomValue(statuses);
}

/**
 * Получение случайного типа кабинета
 * @returns {string}
 */
export function getRandomRoomType() {
  const types = ['office', 'classroom', 'laboratory', 'conference', 'storage'];
  return getRandomValue(types);
}
