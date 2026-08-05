<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Должность сотрудника («Заведующий мастерскими», «Техник первой
     * категории»): роль отвечает за права в системе, должность — за то,
     * как человек называется в штатном расписании.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('position')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};
