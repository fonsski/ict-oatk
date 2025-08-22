<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRoomIdToTicketsTable extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table("tickets", function (Blueprint $table) {
            $table
                ->unsignedBigInteger("room_id")
                ->nullable()
                ->index()
                ->after("location_id");
            $table
                ->foreign("room_id")
                ->references("id")
                ->on("rooms")
                ->onDelete("set null");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("tickets", function (Blueprint $table) {
            $table->dropForeign(["room_id"]);
            $table->dropColumn("room_id");
        });
    }
};
