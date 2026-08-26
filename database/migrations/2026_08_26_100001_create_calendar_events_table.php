<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();

            // Организатор. Если учётку удалят, событие остаётся, но осиротевшим —
            // календарь отдела не должен рассыпаться из-за увольнения сотрудника.
            $table
                ->foreignId('organizer_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->boolean('all_day')->default(false);

            // Свободный текст места и/или бронь конкретного кабинета.
            $table->string('location')->nullable();
            $table
                ->foreignId('room_id')
                ->nullable()
                ->constrained('rooms')
                ->nullOnDelete();

            // Цветовая метка события (ключ палитры, не произвольный CSS).
            $table->string('color')->default('blue');
            $table->string('status')->default('confirmed'); // confirmed | cancelled

            // Правило повторения. Пусто — событие одиночное. Экземпляры серии
            // не материализуются в строки, а разворачиваются на лету по этим
            // полям; отклонения отдельной даты живут в calendar_event_exceptions.
            $table->string('recurrence_freq')->nullable(); // daily | weekly | weekdays | monthly
            $table->unsignedSmallInteger('recurrence_interval')->default(1);
            $table->string('recurrence_byday')->nullable(); // например MO,WE,FR для weekly
            $table->date('recurrence_until')->nullable();
            $table->unsignedSmallInteger('recurrence_count')->nullable();

            $table->timestamps();

            $table->index('starts_at');
            $table->index('room_id');
            $table->index('organizer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
