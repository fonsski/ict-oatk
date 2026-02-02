<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
     * Run the migrations.

    public function up(): void
    {
        
        if (
            Schema::hasTable("tickets") &&
            !Schema::hasColumn("tickets", "equipment_id")
        ) {
            Schema::table("tickets", function (Blueprint $table) {
                
                if (Schema::hasColumn("tickets", "location_id")) {
                    $table
                        ->foreignId("equipment_id")
                        ->nullable()
                        ->after("location_id")
                        ->constrained("equipment")
                        ->nullOnDelete();
                } else {
                    
                    $table
                        ->foreignId("equipment_id")
                        ->nullable()
                        ->constrained("equipment")
                        ->nullOnDelete();
                }
            });
        }
    }

    
     * Reverse the migrations.

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
