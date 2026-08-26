<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Типы документов теперь управляются из системы, а не хардкодом в модели.
return new class extends Migration {
    public function up(): void
    {
        Schema::create("document_types", function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("slug")->unique();
            $table->unsignedInteger("sort_order")->default(0);
            $table->timestamps();
        });

        // Переносим прежние захардкоженные типы.
        $seed = [
            ["name" => "Акт списания", "slug" => "write_off_act", "sort_order" => 1],
            ["name" => "Договор", "slug" => "contract", "sort_order" => 2],
            ["name" => "Счёт", "slug" => "invoice", "sort_order" => 3],
            ["name" => "Накладная", "slug" => "delivery_note", "sort_order" => 4],
            ["name" => "Прочее", "slug" => "other", "sort_order" => 99],
        ];
        foreach ($seed as $row) {
            DB::table("document_types")->insert(
                $row + ["created_at" => now(), "updated_at" => now()],
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists("document_types");
    }
};
