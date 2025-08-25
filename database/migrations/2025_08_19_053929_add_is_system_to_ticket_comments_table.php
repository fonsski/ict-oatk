<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable("ticket_comments")) {
            Schema::table("ticket_comments", function (Blueprint $table) {
                if (!Schema::hasColumn("ticket_comments", "is_system")) {
                    if (Schema::hasColumn("ticket_comments", "content")) {
                        $table
                            ->boolean("is_system")
                            ->default(false)
                            ->after("content");
                    } else {
                        $table->boolean("is_system")->default(false);
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (
            Schema::hasTable("ticket_comments") &&
            Schema::hasColumn("ticket_comments", "is_system")
        ) {
            Schema::table("ticket_comments", function (Blueprint $table) {
                $table->dropColumn("is_system");
            });
        }
    }
};
