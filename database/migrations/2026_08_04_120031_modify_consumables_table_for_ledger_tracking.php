<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * quantity_total (сколько всего пришло) уступает место quantity —
     * текущему остатку, который дальше ведёт журнал stock_movements.
     * Документ закупки переезжает в общее хранилище документов (Блок 1).
     */
    public function up(): void
    {
        Schema::table('consumables', function (Blueprint $table) {
            $table->renameColumn('quantity_total', 'quantity');
        });

        Schema::table('consumables', function (Blueprint $table) {
            $table->unsignedInteger('min_quantity')->nullable()->after('quantity');
            $table
                ->foreignId('room_id')
                ->nullable()
                ->after('min_quantity')
                ->constrained('rooms')
                ->nullOnDelete();

            $table->dropColumn([
                'purchase_document_path',
                'purchase_document_original_name',
                'purchase_document_mime_type',
                'purchase_document_size',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('consumables', function (Blueprint $table) {
            $table->dropConstrainedForeignId('room_id');
            $table->dropColumn('min_quantity');

            $table->string('purchase_document_path')->nullable();
            $table->string('purchase_document_original_name')->nullable();
            $table->string('purchase_document_mime_type')->nullable();
            $table->unsignedBigInteger('purchase_document_size')->default(0);
        });

        Schema::table('consumables', function (Blueprint $table) {
            $table->renameColumn('quantity', 'quantity_total');
        });
    }
};
