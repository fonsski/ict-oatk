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
        Schema::create('consumables', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('unit')->default('шт');
            $table->unsignedInteger('quantity_total');
            $table
                ->foreignId('responsible_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('notes')->nullable();

            // Документ закупки — приватный диск, привязан к расходнику.
            $table->string('purchase_document_path')->nullable();
            $table->string('purchase_document_original_name')->nullable();
            $table->string('purchase_document_mime_type')->nullable();
            $table->unsignedBigInteger('purchase_document_size')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumables');
    }
};
