<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class FixTechnicianRoleSlug extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Исправляем опечатку в slug роли техника
        DB::table("roles")
            ->where("slug", "technican")
            ->update(["slug" => "technician"]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Откатываем изменения
        DB::table("roles")
            ->where("slug", "technician")
            ->update(["slug" => "technican"]);
    }
};
