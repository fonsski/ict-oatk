<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateLocationsTable extends Migration
{
    
     * Run the migrations.

    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        
        DB::table('locations')->insert([
            ['name' => 'Технический отдел', 'description' => 'Место хранения оборудования на обслуживании', 'created_at' => now()],
        ]);
    }

    
     * Reverse the migrations.

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
