<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Ссылка на закупку, по которой единица поступила — чтобы в карточке
     * было видно происхождение, а выгрузка в 1С могла связать поступление
     * с документом поставки.
     */
    public function up(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table
                ->foreignId('purchase_id')
                ->nullable()
                ->after('write_off_id')
                ->constrained('purchases')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropConstrainedForeignId('purchase_id');
        });
    }
};
