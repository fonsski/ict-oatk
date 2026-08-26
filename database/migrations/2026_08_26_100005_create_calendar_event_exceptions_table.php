<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Отклонения отдельных экземпляров повторяющегося события: конкретную
        // дату серии можно отменить или сдвинуть/переименовать, не трогая всю
        // серию. Экземпляры серии не хранятся строками — тут лежат только
        // отличия от вычисленного по правилу.
        Schema::create('calendar_event_exceptions', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('event_id')
                ->constrained('calendar_events')
                ->cascadeOnDelete();

            // Дата исходного экземпляра в серии, к которому относится отклонение.
            $table->date('occurrence_date');

            $table->boolean('is_cancelled')->default(false);

            // Переопределения для перенесённого/изменённого экземпляра.
            // Пусто — берётся из самого события.
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->string('title')->nullable();

            $table->timestamps();

            $table->unique(['event_id', 'occurrence_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_event_exceptions');
    }
};
