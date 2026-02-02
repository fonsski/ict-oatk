<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('knowledge_bases')) {
            Schema::table('knowledge_bases', function (Blueprint $table) {
                if (!Schema::hasColumn('knowledge_bases', 'title')) {
                    $table->string('title')->nullable()->after('id');
                }
                if (!Schema::hasColumn('knowledge_bases', 'slug')) {
                    $table->string('slug')->nullable()->unique()->after('title');
                }
                if (!Schema::hasColumn('knowledge_bases', 'category')) {
                    $table->string('category')->nullable()->after('slug');
                }
                if (!Schema::hasColumn('knowledge_bases', 'excerpt')) {
                    $table->text('excerpt')->nullable()->after('category');
                }
                if (!Schema::hasColumn('knowledge_bases', 'markdown')) {
                    $table->longText('markdown')->nullable()->after('excerpt');
                }
                if (!Schema::hasColumn('knowledge_bases', 'content')) {
                    $table->longText('content')->nullable()->after('markdown');
                }
                if (!Schema::hasColumn('knowledge_bases', 'tags')) {
                    $table->string('tags')->nullable()->after('content');
                }
                if (!Schema::hasColumn('knowledge_bases', 'views_count')) {
                    $table->unsignedBigInteger('views_count')->default(0)->after('tags');
                }
                if (!Schema::hasColumn('knowledge_bases', 'author_id')) {
                    $table->unsignedBigInteger('author_id')->nullable()->after('views_count');
                    $table->foreign('author_id')->references('id')->on('users')->onDelete('set null');
                }
                if (!Schema::hasColumn('knowledge_bases', 'published_at')) {
                    $table->timestamp('published_at')->nullable()->after('author_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('knowledge_bases')) {
            Schema::table('knowledge_bases', function (Blueprint $table) {
                if (Schema::hasColumn('knowledge_bases', 'published_at')) {
                    $table->dropColumn('published_at');
                }
                if (Schema::hasColumn('knowledge_bases', 'author_id')) {
                    $table->dropForeign(['author_id']);
                    $table->dropColumn('author_id');
                }
                if (Schema::hasColumn('knowledge_bases', 'views_count')) {
                    $table->dropColumn('views_count');
                }
                if (Schema::hasColumn('knowledge_bases', 'tags')) {
                    $table->dropColumn('tags');
                }
                if (Schema::hasColumn('knowledge_bases', 'content')) {
                    $table->dropColumn('content');
                }
                if (Schema::hasColumn('knowledge_bases', 'markdown')) {
                    $table->dropColumn('markdown');
                }
                if (Schema::hasColumn('knowledge_bases', 'excerpt')) {
                    $table->dropColumn('excerpt');
                }
                if (Schema::hasColumn('knowledge_bases', 'category')) {
                    $table->dropColumn('category');
                }
                if (Schema::hasColumn('knowledge_bases', 'slug')) {
                    $table->dropUnique(['slug']);
                    $table->dropColumn('slug');
                }
                if (Schema::hasColumn('knowledge_bases', 'title')) {
                    $table->dropColumn('title');
                }
            });
        }
    }
};
