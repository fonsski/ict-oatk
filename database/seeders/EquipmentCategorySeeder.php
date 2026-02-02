<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\EquipmentCategory;

class EquipmentCategorySeeder extends Seeder
{
    
     * Run the database seeds.
     *
     * @return void

    public function run()
    {
        $categories = [
            [
                'name' => 'Компьютер',
                'description' => 'Настольные компьютеры, системные блоки, моноблоки'
            ],
            [
                'name' => 'Ноутбук',
                'description' => 'Портативные компьютеры различных типов'
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
            EquipmentCategory::create([
                'name' => $category['name'],
                'slug' => Str::slug($category['name']),
                'description' => $category['description'],
            ]);
        }
    }
}
