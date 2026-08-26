<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar_events', function (Blueprint $table) {
            // За сколько минут до начала напомнить участникам и организатору.
            // null — не напоминать. Факт отправки по каждому экземпляру
            // хранится в calendar_reminder_dispatches.
            $table->unsignedSmallInteger('reminder_minutes')->nullable()->after('color');
        });
    }

    public function down(): void
    {
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->dropColumn('reminder_minutes');
        });
    }
};
