<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ОС имеет смысл не для всякой техники: монитору или ИБП она не нужна.
     * Признак держим на категории, а не списком названий в коде, — так
     * администратор сам решает, где поле ОС показывать.
     */
    public function up(): void
    {
        Schema::table('equipment_categories', function (Blueprint $table) {
            $table->boolean('has_operating_system')->default(false)->after('description');
        });

        DB::table('equipment_categories')
            ->whereIn('name', ['Компьютер', 'Ноутбук'])
            ->update(['has_operating_system' => true]);

        Schema::table('equipment', function (Blueprint $table) {
            $table
                ->foreignId('operating_system_id')
                ->nullable()
                ->after('category_id')
                ->constrained('operating_systems')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropConstrainedForeignId('operating_system_id');
        });

        Schema::table('equipment_categories', function (Blueprint $table) {
            $table->dropColumn('has_operating_system');
        });
    }
};
