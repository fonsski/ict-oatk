<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();

            // Владелец задачи. Задача личная, поэтому при удалении учётки
            // удаляется вместе с ней — в отличие от событий отдела.
            $table
                ->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Срок. Может быть без времени (на весь день) или вовсе без даты
            // («когда-нибудь»). Отдельный флаг отличает «до 15:00» от «на 26-е».
            $table->dateTime('due_at')->nullable();
            $table->boolean('due_all_day')->default(true);

            $table->timestamp('completed_at')->nullable();
            $table->string('priority')->default('medium'); // low | medium | high

            $table->timestamps();

            $table->index('user_id');
            $table->index('due_at');
            $table->index('completed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_tasks');
    }
};
