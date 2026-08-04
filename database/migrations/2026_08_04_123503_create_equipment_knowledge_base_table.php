<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('equipment_knowledge_base', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('equipment')->cascadeOnDelete();
            $table
                ->foreignId('knowledge_base_id')
                ->constrained('knowledge_bases')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['equipment_id', 'knowledge_base_id'], 'equipment_knowledge_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_knowledge_base');
    }
};
