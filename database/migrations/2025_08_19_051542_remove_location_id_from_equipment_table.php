<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
     * Run the migrations.

    public function up(): void
    {
        
        if (Schema::hasTable("equipment")) {
            Schema::table("equipment", function (Blueprint $table) {
                
                if (Schema::hasColumn("equipment", "location_id")) {
                    
                    $table->dropForeign(["location_id"]);

                    
                    $table->dropColumn("location_id");
                }
            });
        }
    }

    
     * Reverse the migrations.

    public function down(): void
    {
        if (Schema::hasTable("equipment")) {
            Schema::table("equipment", function (Blueprint $table) {
                
                if (!Schema::hasColumn("equipment", "location_id")) {
                    
                    $table
                        ->foreignId("location_id")
                        ->nullable()
                        ->after("status_id")
                        ->constrained("locations")
                        ->nullOnDelete();
                }
            });
        }
    }
};
