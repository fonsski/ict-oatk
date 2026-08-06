<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\EquipmentCategory;

class EquipmentCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $categories = [
            [
                'name' => 'Компьютер',
                'description' => 'Настольные компьютеры, системные блоки, моноблоки',
                'has_operating_system' => true,
            ],
            [
                'name' => 'Ноутбук',
                'description' => 'Портативные компьютеры различных типов',
                'has_operating_system' => true,
            ],
            [
                'name' => 'Принтер',
                'description' => 'Устройства для печати документов'
            ],
            [
                'name' => 'МФУ',
                'description' => 'Многофункциональные устройства (принтер, сканер, копир)'
            ],
            [
                'name' => 'Сканер',
                'description' => 'Устройства для сканирования документов'
            ],
            [
                'name' => 'Монитор',
                'description' => 'Устройства вывода изображения'
            ],
            [
                'name' => 'Проектор',
                'description' => 'Проекционное оборудование'
            ],
            [
                'name' => 'Сетевое оборудование',
                'description' => 'Маршрутизаторы, коммутаторы, точки доступа'
            ],
            [
                'name' => 'Периферийные устройства',
                'description' => 'Клавиатуры, мыши, веб-камеры и прочие устройства ввода'
            ],
            [
                'name' => 'Интерактивная доска',
                'description' => 'Интерактивные доски и панели'
            ],
            [
                'name' => 'Серверное оборудование',
                'description' => 'Серверы и сопутствующее оборудование'
            ],
            [
                'name' => 'ИБП',
                'description' => 'Источники бесперебойного питания'
            ],
            [
                'name' => 'Прочее',
                'description' => 'Другое оборудование, не входящее в основные категории'
            ],
        ];

        foreach ($categories as $category) {
            $hasOperatingSystem = $category['has_operating_system'] ?? false;

            // firstOrCreate, а не create: сидер запускается и при обновлении
            // уже работающей системы, повторный прогон не должен плодить
            // дубли категорий.
            $model = EquipmentCategory::firstOrCreate(
                ['name' => $category['name']],
                [
                    'slug' => Str::slug($category['name']),
                    'description' => $category['description'],
                    'has_operating_system' => $hasOperatingSystem,
                ],
            );

            // Признак «указывать ОС» выставляем и существующим категориям.
            // Миграция, которая его добавила, отрабатывает раньше сидера, и
            // при установке с нуля обновлять ей ещё нечего — поле осталось
            // бы выключенным, а поле выбора ОС не появилось бы нигде.
            if ($hasOperatingSystem && !$model->has_operating_system) {
                $model->update(['has_operating_system' => true]);
            }
        }
    }
}
