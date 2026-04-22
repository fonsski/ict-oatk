<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoomsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique(); // Номер кабинета
            $table->string('name'); // Название кабинета
            $table->text('description')->nullable(); // Описание назначения
            $table->string('floor')->nullable(); // Этаж
            $table->string('building')->nullable(); // Корпус/здание
            $table->integer('capacity')->nullable(); // Вместимость (количество мест)
            $table->string('type')->default('classroom'); // Тип: classroom, lab, office, etc.
            $table->boolean('is_active')->default(true); // Активен ли кабинет
            $table->string('status')->default('available'); // Статус: available, maintenance, occupied
            $table->json('equipment_list')->nullable(); // Список оборудования в JSON
            $table->json('schedule')->nullable(); // Расписание занятий в JSON
            $table->string('responsible_person')->nullable(); // Ответственное лицо
            $table->string('phone')->nullable(); // Контактный телефон
            $table->text('notes')->nullable(); // Дополнительные заметки
            $table->timestamps();
            
            // Индексы для быстрого поиска
            $table->index(['number', 'building']);
            $table->index(['type', 'status']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
