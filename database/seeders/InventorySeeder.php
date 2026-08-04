<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\Equipment;
use App\Models\EquipmentCategory;
use App\Models\EquipmentStatus;
use App\Models\Room;

/**
 * Сидер реального инвентаря ОмАТК (инвентаризация 2025 + акты списания/утилизации).
 *
 * Источник данных: database/seeders/data/inventory.json
 * (сгенерирован ETL из xlsx-таблиц инвентаризации и актов утилизации).
 *
 * ВНИМАНИЕ: сносит демо-данные кабинетов и оборудования перед заливкой.
 */
class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/inventory.json');
        if (!is_file($path)) {
            $this->command->error("InventorySeeder: не найден $path");
            return;
        }

        $items = json_decode(file_get_contents($path), true);
        if (!is_array($items) || empty($items)) {
            $this->command->error('InventorySeeder: inventory.json пустой или битый');
            return;
        }

        // 1) Снести демо-данные оборудования и кабинетов
        Schema::disableForeignKeyConstraints();
        DB::table('equipment_location_histories')->truncate();
        DB::table('equipment_service_histories')->truncate();
        Equipment::query()->truncate();
        Room::query()->truncate();
        Schema::enableForeignKeyConstraints();

        // 2) Статусы (базовые + «Списано»)
        $statusMap = [];
        foreach ([
            ['working', 'Исправно'],
            ['in_service', 'На обслуживании'],
            ['broken', 'Неисправно'],
            ['decommissioned', 'Списано'],
        ] as [$slug, $name]) {
            $statusMap[$slug] = EquipmentStatus::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name]
            )->id;
        }

        // 3) Категории (firstOrCreate по имени; slug — как в проекте)
        $catMap = [];
        foreach (array_unique(array_column($items, 'category')) as $cname) {
            $slug = Str::slug($cname);
            if ($slug === '') {
                $slug = Str::slug(Str::ascii($cname)) ?: 'kat-' . md5($cname);
            }
            $catMap[$cname] = EquipmentCategory::firstOrCreate(
                ['name' => $cname],
                ['slug' => $slug, 'description' => null]
            )->id;
        }

        // 4) Кабинеты (firstOrCreate по номеру)
        $roomMap = [];
        foreach ($items as $it) {
            $num = $it['room_number'] ?? null;
            if ($num === null || $num === '' || isset($roomMap[$num])) {
                continue;
            }
            $roomMap[$num] = Room::firstOrCreate(
                ['number' => $num],
                [
                    'name' => $it['room_name'] ?: $num,
                    'building' => 'Главное здание',
                    'type' => 'other',
                    'is_active' => true,
                    'status' => 'available',
                    'notes' => 'Импорт из инвентаризации 2025',
                ]
            )->id;
        }

        // 5) Оборудование — пачками (bulk insert)
        $now = now();
        $rows = [];
        foreach ($items as $it) {
            $num = $it['room_number'] ?? null;
            $rows[] = [
                'inventory_number' => $it['inventory_number'],
                'accounting_number' => $it['accounting_number'] ?? null,
                'name' => $it['name'] ?? null,
                'model' => $it['model'] ?? null,
                'serial_number' => $it['serial_number'] ?? null,
                'category_id' => $catMap[$it['category']] ?? null,
                'status_id' => $statusMap[$it['status']] ?? $statusMap['working'],
                'room_id' => ($num !== null && $num !== '') ? ($roomMap[$num] ?? null) : null,
                'has_warranty' => false,
                'known_issues' => $it['known_issues'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('equipment')->insert($chunk);
        }

        $this->command->info(
            'InventorySeeder: кабинетов ' . count($roomMap) .
            ', категорий ' . count($catMap) .
            ', оборудования ' . count($rows)
        );
    }
}
