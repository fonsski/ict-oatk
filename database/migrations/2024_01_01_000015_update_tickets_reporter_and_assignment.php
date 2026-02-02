<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateTicketsReporterAndAssignment extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            
            if (!Schema::hasColumn('tickets', 'reporter_name')) {
                $table->string('reporter_name')->nullable();
            }
            if (!Schema::hasColumn('tickets', 'reporter_email')) {
                $table->string('reporter_email')->nullable();
            }
            if (!Schema::hasColumn('tickets', 'reporter_id')) {
                $table->string('reporter_id')->nullable();
            }
            if (!Schema::hasColumn('tickets', 'assigned_to_id')) {
                $table->unsignedBigInteger('assigned_to_id')->nullable()->index();
                $table->foreign('assigned_to_id')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'assigned_to_id')) {
                $table->dropForeign(['assigned_to_id']);
                $table->dropColumn('assigned_to_id');
            }
            if (Schema::hasColumn('tickets', 'reporter_name')) {
                $table->dropColumn('reporter_name');
            }
            if (Schema::hasColumn('tickets', 'reporter_email')) {
                $table->dropColumn('reporter_email');
            }
            if (Schema::hasColumn('tickets', 'reporter_id')) {
                $table->dropColumn('reporter_id');
            }
        });
    }
};
