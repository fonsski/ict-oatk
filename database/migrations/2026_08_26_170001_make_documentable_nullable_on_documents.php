<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Общие документы «библиотеки» на странице /documents не привязаны к сущности,
// поэтому polymorphic-поля становятся необязательными. Прежние документы,
// прикреплённые к оборудованию/закупкам, остаются как есть.
return new class extends Migration {
    public function up(): void
    {
        Schema::table("documents", function (Blueprint $table) {
            $table->string("documentable_type")->nullable()->change();
            $table->unsignedBigInteger("documentable_id")->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table("documents", function (Blueprint $table) {
            $table->string("documentable_type")->nullable(false)->change();
            $table->unsignedBigInteger("documentable_id")->nullable(false)->change();
        });
    }
};
