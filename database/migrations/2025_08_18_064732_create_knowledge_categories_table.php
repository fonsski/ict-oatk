<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKnowledgeCategoriesTable extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("knowledge_categories", function (Blueprint $table) {
            $table->id();
            $table->string("name")->unique();
            $table->string("slug")->unique();
            $table->string("description")->nullable();
            $table->string("icon")->nullable();
            $table->string("color", 7)->default("#6B7280"); // hex color
            $table->integer("sort_order")->default(0);
            $table->boolean("is_active")->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("knowledge_categories");
    }
};