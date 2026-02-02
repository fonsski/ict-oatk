<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
     * Run the migrations.

    public function up(): void
    {
        if (Schema::hasTable("users")) {
            if (!Schema::hasColumn("users", "telegram_id")) {
                Schema::table("users", function (Blueprint $table) {
                    $table->string("telegram_id")->nullable()->after("phone");
                });
            }
        }
    }

    
     * Reverse the migrations.

    public function down(): void
    {
        if (
            Schema::hasTable("users") &&
            Schema::hasColumn("users", "telegram_id")
        ) {
            Schema::table("users", function (Blueprint $table) {
                $table->dropColumn("telegram_id");
            });
        }
    }
};
