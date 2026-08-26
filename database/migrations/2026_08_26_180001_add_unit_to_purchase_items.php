<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Единица измерения позиции — колонка «Ед. измер.» из печатной заявки ТМЦ.
return new class extends Migration {
    public function up(): void
    {
        Schema::table("purchase_items", function (Blueprint $table) {
            $table->string("unit", 32)->default("шт.")->after("quantity");
        });
    }

    public function down(): void
    {
        Schema::table("purchase_items", function (Blueprint $table) {
            $table->dropColumn("unit");
        });
    }
};
