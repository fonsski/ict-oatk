<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Проверяем существует ли таблица equipment
        if (Schema::hasTable("equipment")) {
            Schema::table("equipment", function (Blueprint $table) {
                // Проверяем существует ли колонка location_id
                if (Schema::hasColumn("equipment", "location_id")) {
                    // Сначала удаляем внешний ключ
                    $table->dropForeign(["location_id"]);

                    // Затем удаляем само поле
                    $table->dropColumn("location_id");
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable("equipment")) {
            Schema::table("equipment", function (Blueprint $table) {
                // Проверяем существует ли колонка location_id
                if (!Schema::hasColumn("equipment", "location_id")) {
                    // Добавляем поле обратно
                    $table
                        ->foreignId("location_id")
                        ->nullable()
                        ->after("status_id")
                        ->constrained("locations")
                        ->nullOnDelete();
                }
            });
        }
    }
};
