<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
     * Run the migrations.

    public function up(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            
            $table->string('accounting_number')->nullable()->after('inventory_number');
            
            
            $table->index('accounting_number');
        });
    }

    
     * Reverse the migrations.

    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            
            $table->dropIndex(['accounting_number']);
            
            
            $table->dropColumn('accounting_number');
        });
    }
};
