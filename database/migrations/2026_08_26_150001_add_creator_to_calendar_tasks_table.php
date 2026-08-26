<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar_tasks', function (Blueprint $table) {
            // Автор задачи. user_id остаётся исполнителем (кому поручена).
            // У старых задач автор = исполнитель, проставим ниже.
            $table
                ->foreignId('created_by_user_id')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->nullOnDelete();
        });

        // Для уже существующих задач автором считаем исполнителя.
        \DB::statement('UPDATE calendar_tasks SET created_by_user_id = user_id WHERE created_by_user_id IS NULL');
    }

    public function down(): void
    {
        Schema::table('calendar_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by_user_id');
        });
    }
};
