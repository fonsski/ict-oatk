<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Заявка на приобретение ТМЦ — простой заполняемый лист (без привязки к
// инвентарю и проведения). Печатается по шаблону колледжа и сохраняется в
// «Документы».
return new class extends Migration {
    public function up(): void
    {
        Schema::create("tmc_requests", function (Blueprint $table) {
            $table->id();
            $table->string("number", 50)->nullable();
            $table->date("date");
            $table->text("purpose")->nullable();
            $table->decimal("total_sum", 12, 2)->default(0);
            $table
                ->foreignId("created_by_user_id")
                ->nullable()
                ->constrained("users")
                ->nullOnDelete();
            $table->timestamps();
        });

        Schema::create("tmc_request_items", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("tmc_request_id")
                ->constrained("tmc_requests")
                ->cascadeOnDelete();
            $table->string("name");
            $table->unsignedInteger("quantity")->default(1);
            $table->string("unit", 32)->default("шт.");
            $table->decimal("unit_price", 12, 2)->default(0);
            $table->decimal("sum", 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("tmc_request_items");
        Schema::dropIfExists("tmc_requests");
    }
};
