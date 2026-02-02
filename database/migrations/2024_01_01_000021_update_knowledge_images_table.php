<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateKnowledgeImagesTable extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('knowledge_images')) {
            Schema::table('knowledge_images', function (Blueprint $table) {
                if (!Schema::hasColumn('knowledge_images', 'knowledge_base_id')) {
                    $table->unsignedBigInteger('knowledge_base_id')->nullable()->after('id');
                    $table->foreign('knowledge_base_id')->references('id')->on('knowledge_bases')->onDelete('cascade');
                }
                if (!Schema::hasColumn('knowledge_images', 'path')) {
                    $table->string('path')->nullable()->after('knowledge_base_id');
                }
                if (!Schema::hasColumn('knowledge_images', 'alt')) {
                    $table->string('alt')->nullable()->after('path');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('knowledge_images')) {
            Schema::table('knowledge_images', function (Blueprint $table) {
                if (Schema::hasColumn('knowledge_images', 'alt')) {
                    $table->dropColumn('alt');
                }
                if (Schema::hasColumn('knowledge_images', 'path')) {
                    $table->dropColumn('path');
                }
                if (Schema::hasColumn('knowledge_images', 'knowledge_base_id')) {
                    $table->dropForeign(['knowledge_base_id']);
                    $table->dropColumn('knowledge_base_id');
                }
            });
        }
    }
};
