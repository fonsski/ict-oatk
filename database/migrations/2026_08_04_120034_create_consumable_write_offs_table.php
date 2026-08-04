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
        Schema::create('consumable_write_offs', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->date('written_off_at');
            $table->string('reason')->nullable();
            $table
                ->foreignId('written_off_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumable_write_offs');
    }
};
