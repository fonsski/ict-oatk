<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('knowledge_bases', function (Blueprint $table) {
            // Индекс: soft-delete scope добавляет "deleted_at is null"
            // в каждый запрос по статьям.
            $table->softDeletes()->index();
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_bases', function (Blueprint $table) {
            $table->dropIndex(['deleted_at']);
            $table->dropSoftDeletes();
        });
    }
};
