<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Боевое наполнение: только то, без чего система не работает, —
 * справочники, реальный инвентарь (оборудование + кабинеты) и сотрудники.
 *
 * Никаких демо-заявок, тестовых пользователей и показательных закупок.
 *
 * Запуск: php artisan db:seed --class=ProductionSeeder --force
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Справочники — нужны, чтобы оборудование вообще могло существовать.
            RoleSeeder::class,
            EquipmentCategorySeeder::class,
            KnowledgeCategorySeeder::class,
            OperatingSystemSeeder::class,

            // Реальный инвентарь колледжа: кабинеты + оборудование.
            InventorySeeder::class,

            // Учётные записи отдела.
            StaffUserSeeder::class,
        ]);
    }
}
