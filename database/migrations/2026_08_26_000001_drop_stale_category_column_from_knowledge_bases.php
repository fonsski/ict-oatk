<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Столбец-строка `category` должен был исчезнуть ещё в миграции перехода на
// `category_id` (2025_08_18_064802), но в части баз он остался. Пока он есть,
// Eloquent отдаёт по `$article->category` значение столбца (NULL), затеняя
// связь category() — из-за этого статьи показывались как «Без категории».
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn("knowledge_bases", "category")) {
            Schema::table("knowledge_bases", function (Blueprint $table) {
                $table->dropColumn("category");
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn("knowledge_bases", "category")) {
            Schema::table("knowledge_bases", function (Blueprint $table) {
                $table->string("category")->nullable()->after("slug");
            });
        }
    }
};
