<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateEquipmentStatusesTable extends Migration
{
    
     * Run the migrations.

    public function up(): void
    {
        Schema::create('equipment_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        
        DB::table('equipment_statuses')->insert([
            ['name' => 'Исправно', 'slug' => 'working', 'created_at' => now()],
            ['name' => 'На обслуживании', 'slug' => 'in_service', 'created_at' => now()],
            ['name' => 'Неисправно', 'slug' => 'broken', 'created_at' => now()],
        ]);
    }

    
     * Reverse the migrations.

    public function down(): void
    {
        Schema::dropIfExists('equipment_statuses');
    }
};
