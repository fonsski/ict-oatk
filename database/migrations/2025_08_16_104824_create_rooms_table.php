<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoomsTable extends Migration
{
    
     * Run the migrations.

    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique(); 
            $table->string('name'); 
            $table->text('description')->nullable(); 
            $table->string('floor')->nullable(); 
            $table->string('building')->nullable(); 
            $table->integer('capacity')->nullable(); 
            $table->string('type')->default('classroom'); 
            $table->boolean('is_active')->default(true); 
            $table->string('status')->default('available'); 
            $table->json('equipment_list')->nullable(); 
            $table->json('schedule')->nullable(); 
            $table->string('responsible_person')->nullable(); 
            $table->string('phone')->nullable(); 
            $table->text('notes')->nullable(); 
            $table->timestamps();
            
            
            $table->index(['number', 'building']);
            $table->index(['type', 'status']);
            $table->index('is_active');
        });
    }

    
     * Reverse the migrations.

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
