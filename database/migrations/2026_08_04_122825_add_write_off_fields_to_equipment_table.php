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
     * Ссылка на акт, по которому единица списана — чтобы в карточке
     * оборудования сразу видеть, когда и на каком основании это произошло.
     */
    public function up(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->date('written_off_at')->nullable()->after('known_issues');
            $table
                ->foreignId('write_off_id')
                ->nullable()
                ->after('written_off_at')
                ->constrained('write_offs')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropConstrainedForeignId('write_off_id');
            $table->dropColumn('written_off_at');
        });
    }
};
