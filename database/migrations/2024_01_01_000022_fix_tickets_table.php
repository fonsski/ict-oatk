<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixTicketsTable extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'title')) {
                $table->string('title')->after('id');
            }
            if (!Schema::hasColumn('tickets', 'category')) {
                $table->string('category')->after('title');
            }
            if (!Schema::hasColumn('tickets', 'priority')) {
                $table->string('priority')->after('category');
            }
            if (!Schema::hasColumn('tickets', 'description')) {
                $table->text('description')->after('priority');
            }
            if (!Schema::hasColumn('tickets', 'location_id')) {
                $table->foreignId('location_id')->nullable()->after('reporter_id')->constrained('locations')->nullOnDelete();
            }
            if (!Schema::hasColumn('tickets', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('location_id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('tickets', 'status')) {
                $table->string('status')->default('open')->after('assigned_to_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['title', 'category', 'priority', 'description', 'status']);
            if (Schema::hasColumn('tickets', 'location_id')) {
                $table->dropForeign(['location_id']);
                $table->dropColumn('location_id');
            }
            if (Schema::hasColumn('tickets', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });
    }
};
