

import { testRooms } from '../fixtures/test-data.js';


export async function createRoom(page, roomType) {
  const room = testRooms[roomType];
  
  await page.goto('/room/create');
  await page.waitForLoadState('networkidle');
  
  // Заполняем форму кабинета
  await page.fill('input[name="number"]', room.number);
  await page.fill('input[name="name"]', room.name);
  await page.selectOption('select[name="type"]', room.type);
  await page.fill('input[name="building"]', room.building);
  await page.fill('input[name="floor"]', room.floor);
  
  // Отправляем форму
  await page.click('button[type="submit"]');
  
  // Ждем перенаправления
  await page.waitForURL('**/room
export async function expectRoomCreated(page, number) {
  await page.waitForSelector(`text=${number}`);
}


export async function updateRoom(page, roomId, updates) {
  await page.goto(`/room/${roomId}/edit`);
  await page.waitForLoadState('networkidle');
  
  // Обновляем поля
  if (updates.name) {
    await page.fill('input[name="name"]', updates.name);
  }
  if (updates.building) {
    await page.fill('input[name="building"]', updates.building);
  }
  if (updates.floor) {
    await page.fill('input[name="floor"]', updates.floor);
  }
  
  // Сохраняем изменения
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');
}


export async function changeRoomStatus(page, roomId, status) {
  await page.goto(`/room/${roomId}`);
  await page.waitForLoadState('networkidle');
  
  // Находим кнопку изменения статуса
  await page.click('[data-testid="change-status"]');
  
  // Выбираем новый статус
  await page.selectOption('select[name="status"]', status);
  
  // Подтверждаем изменение
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');
}
