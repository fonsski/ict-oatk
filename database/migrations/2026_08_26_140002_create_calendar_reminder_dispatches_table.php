<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Отметка о разосланном напоминании по конкретному экземпляру события.
        // Для повторяющихся серий это единственный способ не слать напоминание
        // об одном и том же понедельнике дважды — серия хранится одной строкой.
        Schema::create('calendar_reminder_dispatches', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('event_id')
                ->constrained('calendar_events')
                ->cascadeOnDelete();
            $table->dateTime('occurrence_starts_at');
            $table->timestamp('sent_at');

            $table->unique(['event_id', 'occurrence_starts_at'], 'cal_reminder_dispatch_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_reminder_dispatches');
    }
};
