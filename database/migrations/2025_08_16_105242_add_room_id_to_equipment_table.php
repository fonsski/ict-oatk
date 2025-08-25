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
        Schema::table("equipment", function (Blueprint $table) {
            // Check if location_id exists
            if (Schema::hasColumn("equipment", "location_id")) {
                $table
                    ->foreignId("room_id")
                    ->nullable()
                    ->after("location_id")
                    ->constrained("rooms")
                    ->onDelete("set null");
            } else {
                // If location_id doesn't exist, add after status_id
                $table
                    ->foreignId("room_id")
                    ->nullable()
                    ->after("status_id")
                    ->constrained("rooms")
                    ->onDelete("set null");
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("equipment", function (Blueprint $table) {
            $table->dropForeign(["room_id"]);
            $table->dropColumn("room_id");
        });
    }
};
