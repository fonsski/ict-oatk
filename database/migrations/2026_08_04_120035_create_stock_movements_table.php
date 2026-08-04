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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consumable_id')->constrained('consumables')->cascadeOnDelete();
            $table->string('type'); // income | outcome
            $table->unsignedInteger('quantity');
            $table->string('reason')->nullable();

            // outcome: расходник выдан/установлен в конкретное оборудование.
            $table
                ->foreignId('equipment_id')
                ->nullable()
                ->constrained('equipment')
                ->nullOnDelete();

            // income: приход по закупке.
            $table
                ->foreignId('purchase_id')
                ->nullable()
                ->constrained('purchases')
                ->nullOnDelete();

            // outcome: часть оформленного массового акта списания.
            $table
                ->foreignId('consumable_write_off_id')
                ->nullable()
                ->constrained('consumable_write_offs')
                ->nullOnDelete();

            $table
                ->foreignId('moved_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->date('moved_at');
            $table->timestamps();

            $table->index(['consumable_id', 'type']);
            $table->index(['equipment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
