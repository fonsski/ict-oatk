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
        Schema::create('write_off_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('write_off_id')->constrained('write_offs')->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained('equipment')->cascadeOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();

            // Одна и та же единица не может дважды попасть в один акт.
            $table->unique(['write_off_id', 'equipment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('write_off_items');
    }
};
