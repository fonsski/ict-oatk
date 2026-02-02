

import { testEquipment, testRooms } from '../fixtures/test-data.js';


export async function createEquipment(page, equipmentType) {
  const equipment = testEquipment[equipmentType];
  
  await page.goto('/equipment/create');
  await page.waitForLoadState('networkidle');
  
  // Заполняем форму оборудования
  await page.fill('input[name="name"]', equipment.name);
  await page.fill('input[name="model"]', equipment.model);
  await page.fill('input[name="serial_number"]', equipment.serial_number);
  await page.fill('input[name="inventory_number"]', equipment.inventory_number);
  
  // Выбираем статус
  await page.selectOption('select[name="status"]', equipment.status);
  
  // Выбираем кабинет
  await page.selectOption('select[name="room_id"]', { index: 1 });
  
  // Отправляем форму
  await page.click('button[type="submit"]');
  
  // Ждем перенаправления
  await page.waitForURL('**/equipment
export async function expectEquipmentCreated(page, name) {
  await page.waitForSelector(`text=${name}`);
}


export async function updateEquipment(page, equipmentId, updates) {
  await page.goto(`/equipment/${equipmentId}/edit`);
  await page.waitForLoadState('networkidle');
  
  // Обновляем поля
  if (updates.name) {
    await page.fill('input[name="name"]', updates.name);
  }
  if (updates.model) {
    await page.fill('input[name="model"]', updates.model);
  }
  if (updates.status) {
    await page.selectOption('select[name="status"]', updates.status);
  }
  
  // Сохраняем изменения
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');
}


export async function moveEquipment(page, equipmentId, newRoomId) {
  await page.goto(`/equipment/${equipmentId}/move`);
  await page.waitForLoadState('networkidle');
  
  // Выбираем новый кабинет
  await page.selectOption('select[name="room_id"]', newRoomId);
  
  // Добавляем комментарий
  await page.fill('textarea[name="comment"]', 'Перемещение оборудования');
  
  // Подтверждаем перемещение
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');
}


export async function createServiceRecord(page, equipmentId, serviceData) {
  await page.goto(`/equipment/${equipmentId}/service/create`);
  await page.waitForLoadState('networkidle');
  
  // Заполняем форму обслуживания
  await page.fill('input[name="service_date"]', serviceData.date);
  await page.fill('textarea[name="description"]', serviceData.description);
  await page.fill('input[name="performed_by"]', serviceData.performed_by);
  
  if (serviceData.cost) {
    await page.fill('input[name="cost"]', serviceData.cost);
  }
  
  // Отправляем форму
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');
}
