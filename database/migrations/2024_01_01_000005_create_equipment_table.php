<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEquipmentTable extends Migration
{
    
     * Run the migrations.

    public function up(): void
    {
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->string('inventory_number')->unique();
            $table->foreignId('status_id')->constrained('equipment_statuses');
            $table->foreignId('location_id')->constrained('locations');
            $table->date('last_service_date')->nullable();
            $table->text('service_comment')->nullable();
            $table->text('known_issues')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    
     * Reverse the migrations.

    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
