<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNameToEquipmentTable extends Migration
{
    
     * Run the migrations.
     *
     * @return void

    public function up()
    {
        if (!Schema::hasColumn('equipment', 'name')) {
            Schema::table('equipment', function (Blueprint $table) {
                $table->string('name')->nullable()->after('inventory_number');
            });
        }
    }

    
     * Reverse the migrations.
     *
     * @return void

    public function down()
    {
        if (Schema::hasColumn('equipment', 'name')) {
            Schema::table('equipment', function (Blueprint $table) {
                $table->dropColumn('name');
            });
        }
    }
};
