<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
     * Run the migrations.

    public function up(): void
    {
        Schema::table("equipment", function (Blueprint $table) {
            
            if (Schema::hasColumn("equipment", "room_id")) {
                $table
                    ->foreignId("initial_room_id")
                    ->nullable()
                    ->after("room_id")
                    ->constrained("rooms")
                    ->nullOnDelete();
            } else {
                
                $table
                    ->foreignId("initial_room_id")
                    ->nullable()
                    ->after("status_id")
                    ->constrained("rooms")
                    ->nullOnDelete();
            }
        });
    }

    
     * Reverse the migrations.

    public function down(): void
    {
        Schema::table("equipment", function (Blueprint $table) {
            if (Schema::hasColumn("equipment", "initial_room_id")) {
                $table->dropForeign(["initial_room_id"]);
                $table->dropColumn("initial_room_id");
            }
        });
    }
};
