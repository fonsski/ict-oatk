<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
     * Run the migrations.

    public function up(): void
    {
        if (Schema::hasTable("tickets")) {
            Schema::table("tickets", function (Blueprint $table) {
                if (Schema::hasColumn("tickets", "location_id")) {
                    $table
                        ->unsignedBigInteger("room_id")
                        ->nullable()
                        ->index()
                        ->after("location_id");
                } else {
                    
                    $table->unsignedBigInteger("room_id")->nullable()->index();
                }

                $table
                    ->foreign("room_id")
                    ->references("id")
                    ->on("rooms")
                    ->onDelete("set null");
            });
        }
    }

    
     * Reverse the migrations.

    public function down(): void
    {
        if (
            Schema::hasTable("tickets") &&
            Schema::hasColumn("tickets", "room_id")
        ) {
            Schema::table("tickets", function (Blueprint $table) {
                $table->dropForeign(["room_id"]);
                $table->dropColumn("room_id");
            });
        }
    }
};
