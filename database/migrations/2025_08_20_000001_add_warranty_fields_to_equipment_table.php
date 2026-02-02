<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWarrantyFieldsToEquipmentTable extends Migration
{
    
     * Run the migrations.
     *
     * @return void

    public function up()
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->boolean('has_warranty')->default(false)->after('room_id');
            $table->date('warranty_end_date')->nullable()->after('has_warranty');
        });
    }

    
     * Reverse the migrations.
     *
     * @return void

    public function down()
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropColumn('has_warranty');
            $table->dropColumn('warranty_end_date');
        });
    }
}
