<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateKnowledgeBasesCategoryToForeignKey extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Сначала создаем базовые категории
        $this->createDefaultCategories();

        // Добавляем новое поле category_id
        Schema::table("knowledge_bases", function (Blueprint $table) {
            $table
                ->unsignedBigInteger("category_id")
                ->nullable()
                ->after("category");
            $table
                ->foreign("category_id")
                ->references("id")
                ->on("knowledge_categories")
                ->onDelete("set null");
        });

        // Мигрируем данные из category в category_id
        $this->migrateExistingData();

        // Удаляем старое поле category
        Schema::table("knowledge_bases", function (Blueprint $table) {
            $table->dropColumn("category");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Добавляем обратно поле category
        Schema::table("knowledge_bases", function (Blueprint $table) {
            $table->string("category")->nullable()->after("slug");
        });

        // Мигрируем данные обратно из category_id в category.
        // Только query builder: модели в миграциях завязываются на текущую
        // схему и глобальные scope-ы, которых на этом шаге ещё нет.
        $rows = DB::table("knowledge_bases")->whereNotNull("category_id")->get();
        foreach ($rows as $kb) {
            $slug = DB::table("knowledge_categories")
                ->where("id", $kb->category_id)
                ->value("slug");
            if ($slug) {
                DB::table("knowledge_bases")
                    ->where("id", $kb->id)
                    ->update(["category" => $slug]);
            }
        }

        // Удаляем внешний ключ и поле category_id
        Schema::table("knowledge_bases", function (Blueprint $table) {
            $table->dropForeign(["category_id"]);
            $table->dropColumn("category_id");
        });
    }

    private function createDefaultCategories()
    {
        $categories = [
            [
                "name" => "Оборудование",
                "slug" => "hardware",
                "description" =>
                    "Статьи о компьютерном оборудовании и периферии",
                "icon" => "computer-desktop",
                "color" => "#059669",
                "sort_order" => 1,
            ],
            [
                "name" => "Программное обеспечение",
                "slug" => "software",
                "description" => "Статьи о программах и приложениях",
                "icon" => "code-bracket",
                "color" => "#3B82F6",
                "sort_order" => 2,
            ],
            [
                "name" => "Сеть и интернет",
                "slug" => "network",
                "description" =>
                    "Статьи о настройке сети и интернет-подключения",
                "icon" => "globe-alt",
                "color" => "#8B5CF6",
                "sort_order" => 3,
            ],
            [
                "name" => "Другое",
                "slug" => "other",
                "description" => "Прочие статьи",
                "icon" => "ellipsis-horizontal",
                "color" => "#6B7280",
                "sort_order" => 4,
            ],
        ];

        foreach ($categories as $category) {
            $exists = DB::table("knowledge_categories")
                ->where("slug", $category["slug"])
                ->exists();
            if (!$exists) {
                DB::table("knowledge_categories")->insert(
                    $category + [
                        "is_active" => true,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ],
                );
            }
        }
    }

    private function migrateExistingData()
    {
        $otherId = DB::table("knowledge_categories")
            ->where("slug", "other")
            ->value("id");

        $rows = DB::table("knowledge_bases")->whereNotNull("category")->get();
        foreach ($rows as $kb) {
            $categoryId =
                DB::table("knowledge_categories")
                    ->where("slug", $kb->category)
                    ->value("id") ?? $otherId;

            if ($categoryId) {
                DB::table("knowledge_bases")
                    ->where("id", $kb->id)
                    ->update(["category_id" => $categoryId]);
            }
        }
    }
};
