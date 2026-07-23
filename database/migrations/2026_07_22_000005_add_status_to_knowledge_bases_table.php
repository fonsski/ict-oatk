<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('knowledge_bases', function (Blueprint $table) {
            // Жизненный цикл статьи: draft (черновик) → published → archived.
            // По умолчанию published, чтобы существующие статьи остались видимыми.
            $table
                ->string('status')
                ->default('published')
                ->after('content')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_bases', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};
