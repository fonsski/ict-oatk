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
        Schema::create('consumable_allocations', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('consumable_id')
                ->constrained('consumables')
                ->cascadeOnDelete();

            // Null, если расходник списан прямо со склада, минуя оборудование.
            $table
                ->foreignId('equipment_id')
                ->nullable()
                ->constrained('equipment')
                ->nullOnDelete();

            $table->unsignedInteger('quantity');
            $table->string('status')->default('installed'); // installed | written_off

            $table->date('installed_at')->nullable();
            $table
                ->foreignId('installed_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('note')->nullable();

            $table->date('written_off_at')->nullable();
            $table
                ->foreignId('written_off_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('written_off_reason')->nullable();

            // Документ списания — приватный диск, привязан к конкретной записи.
            $table->string('write_off_document_path')->nullable();
            $table->string('write_off_document_original_name')->nullable();
            $table->string('write_off_document_mime_type')->nullable();
            $table->unsignedBigInteger('write_off_document_size')->default(0);

            $table->timestamps();

            $table->index(['equipment_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumable_allocations');
    }
};
