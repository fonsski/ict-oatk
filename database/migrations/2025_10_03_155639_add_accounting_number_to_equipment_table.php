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
            // Добавляем поле учётного номера после инвентарного номера
            $table->string('accounting_number')->nullable()->after('inventory_number');
            
            // Добавляем индекс для быстрого поиска по учётному номеру
            $table->index('accounting_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            // Удаляем индекс
            $table->dropIndex(['accounting_number']);
            
            // Удаляем поле учётного номера
            $table->dropColumn('accounting_number');
        });
    }
};
