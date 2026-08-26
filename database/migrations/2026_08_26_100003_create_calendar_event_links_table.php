<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Полиморфная связь события с сущностями системы: заявкой,
        // оборудованием, документом. Одно событие может ссылаться на
        // несколько объектов сразу («выезд по заявке #12 с ноутбуком»).
        // Бронь кабинета не здесь — она в calendar_events.room_id, потому
        // что требует проверки занятости по времени.
        Schema::create('calendar_event_links', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('event_id')
                ->constrained('calendar_events')
                ->cascadeOnDelete();
            $table->morphs('linkable'); // linkable_type, linkable_id
            $table->timestamps();

            $table->unique(['event_id', 'linkable_type', 'linkable_id'], 'event_link_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_event_links');
    }
};
