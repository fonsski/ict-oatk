

import { testUsers, testTickets, testEquipment, testRooms } from '../fixtures/test-data.js';


export function getTestUser(role) {
  return testUsers[role];
}


export function getTestTicket(type) {
  return testTickets[type];
}


export function getTestEquipment(type) {
  return testEquipment[type];
}


export function getTestRoom(type) {
  return testRooms[type];
}


export function generateRandomPhone() {
  const random = Math.floor(Math.random() * 10000000000);
  return `(999) ${random.toString().slice(0, 3)}-${random.toString().slice(3, 5)}-${random.toString().slice(5, 7)}`;
}


export function generateRandomEmail() {
  const random = Math.floor(Math.random() * 1000000);
  return `test${random}@example.com`;
}


export function generateRandomName() {
  const names = ['Иван', 'Петр', 'Сидор', 'Алексей', 'Дмитрий', 'Сергей', 'Андрей', 'Николай'];
  const surnames = ['Иванов', 'Петров', 'Сидоров', 'Алексеев', 'Дмитриев', 'Сергеев', 'Андреев', 'Николаев'];
  
  const randomName = names[Math.floor(Math.random() * names.length)];
  const randomSurname = surnames[Math.floor(Math.random() * surnames.length)];
  
  return `${randomName} ${randomSurname}`;
}


export function generateRandomRoomNumber() {
  const floor = Math.floor(Math.random() * 5) + 1;
  const room = Math.floor(Math.random() * 20) + 1;
  return `${floor}${room.toString().padStart(2, '0')}`;
}


export function generateRandomSerialNumber() {
  const prefix = ['DL', 'HP', 'LG', 'SN', 'AS'];
  const randomPrefix = prefix[Math.floor(Math.random() * prefix.length)];
  const randomNumber = Math.floor(Math.random() * 1000000000);
  return `${randomPrefix}${randomNumber}`;
}


export function generateRandomInventoryNumber() {
  const random = Math.floor(Math.random() * 100000);
  return `INV${random.toString().padStart(5, '0')}`;
}


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


export async function cleanupTestData(page) {
  // Эта функция может быть реализована для очистки тестовых данных
  // после выполнения тестов, если это необходимо
  console.log('Очистка тестовых данных...');
}


export async function createTestDataInDB(page, entityType, data) {
  // Эта функция может быть реализована для создания тестовых данных
  // непосредственно в базе данных через API или другие методы
  console.log(`Создание тестовых данных: ${entityType}`, data);
}


export async function deleteTestDataFromDB(page, entityType, identifier) {
  // Эта функция может быть реализована для удаления тестовых данных
  // из базы данных после выполнения тестов
  console.log(`Удаление тестовых данных: ${entityType} - ${identifier}`);
}


export async function getCreatedEntityId(page) {
  const url = page.url();
  const match = url.match(/\/(\d+)(?:\/|$)/);
  return match ? match[1] : null;
}


export async function waitForEntityCreation(page, expectedText) {
  await page.waitForSelector(`text=${expectedText}`);
}


export async function expectEntityCreated(page, expectedText) {
  await page.waitForSelector(`text=${expectedText}`);
}


export function getRandomValue(array) {
  return array[Math.floor(Math.random() * array.length)];
}


export function getRandomTicketStatus() {
  const statuses = ['open', 'in_progress', 'resolved', 'closed'];
  return getRandomValue(statuses);
}


export function getRandomTicketPriority() {
  const priorities = ['low', 'medium', 'high', 'urgent'];
  return getRandomValue(priorities);
}


export function getRandomTicketCategory() {
  const categories = ['hardware', 'software', 'network', 'account', 'other'];
  return getRandomValue(categories);
}


export function getRandomEquipmentStatus() {
  const statuses = ['active', 'inactive', 'maintenance', 'broken'];
  return getRandomValue(statuses);
}


export function getRandomRoomType() {
  const types = ['office', 'classroom', 'laboratory', 'conference', 'storage'];
  return getRandomValue(types);
}
