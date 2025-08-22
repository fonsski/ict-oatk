<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            // Сначала удаляем внешний ключ
            $table->dropForeign(['location_id']);

            // Затем удаляем само поле
            $table->dropColumn('location_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            // Добавляем поле обратно
            $table->foreignId('location_id')->nullable()->after('status_id')->constrained('locations')->nullOnDelete();
        });
    }
};
