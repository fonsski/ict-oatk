<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\KnowledgeCategory;

class KnowledgeCategorySeeder extends Seeder
{
    
     * Run the database seeds.

    public function run(): void
    {
        $categories = [
            [
                "name" => "Оборудование",
                "slug" => "hardware",
                "description" =>
                    "Статьи о компьютерном оборудовании и периферии",
                "icon" => "computer-desktop",
                "color" => "
                "sort_order" => 1,
                "is_active" => true,
            ],
            [
                "name" => "Программное обеспечение",
                "slug" => "software",
                "description" => "Статьи о программах и приложениях",
                "icon" => "code-bracket",
                "color" => "
                "sort_order" => 2,
                "is_active" => true,
            ],
            [
                "name" => "Сеть и интернет",
                "slug" => "network",
                "description" =>
                    "Статьи о настройке сети и интернет-подключения",
                "icon" => "globe-alt",
                "color" => "
                "sort_order" => 3,
                "is_active" => true,
            ],
            [
                "name" => "Другое",
                "slug" => "other",
                "description" => "Прочие статьи",
                "icon" => "ellipsis-horizontal",
                "color" => "
                "sort_order" => 4,
                "is_active" => true,
            ],
        ];

        foreach ($categories as $category) {
            KnowledgeCategory::firstOrCreate(
                ["slug" => $category["slug"]],
                $category,
            );
        }
    }
}
