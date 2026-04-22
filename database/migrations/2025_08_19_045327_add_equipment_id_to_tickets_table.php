<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if tickets table exists before attempting to modify it
        if (
            Schema::hasTable("tickets") &&
            !Schema::hasColumn("tickets", "equipment_id")
        ) {
            Schema::table("tickets", function (Blueprint $table) {
                // Check if location_id exists
                if (Schema::hasColumn("tickets", "location_id")) {
                    $table
                        ->foreignId("equipment_id")
                        ->nullable()
                        ->after("location_id")
                        ->constrained("equipment")
                        ->nullOnDelete();
                } else {
                    // If location_id doesn't exist, add after user_id or as the last column
                    $table
                        ->foreignId("equipment_id")
                        ->nullable()
                        ->constrained("equipment")
                        ->nullOnDelete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (
            Schema::hasTable("tickets") &&
            Schema::hasColumn("tickets", "equipment_id")
        ) {
            Schema::table("tickets", function (Blueprint $table) {
                $table->dropForeign(["equipment_id"]);
                $table->dropColumn("equipment_id");
            });
        }
    }
};
