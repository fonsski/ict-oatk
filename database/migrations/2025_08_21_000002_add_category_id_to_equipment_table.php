<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
     * Run the migrations.
     *
     * @return void

    public function up(): void
    {
        Schema::table("equipment", function (Blueprint $table) {
            
            if (Schema::hasTable("equipment_categories")) {
                $table
                    ->foreignId("category_id")
                    ->nullable()
                    ->after("inventory_number")
                    ->constrained("equipment_categories")
                    ->nullOnDelete();
            }
        });
    }

    
     * Reverse the migrations.
     *
     * @return void

    public function down(): void
    {
        Schema::table("equipment", function (Blueprint $table) {
            if (Schema::hasColumn("equipment", "category_id")) {
                $table->dropForeign(["category_id"]);
                $table->dropColumn("category_id");
            }
        });
    }
};
