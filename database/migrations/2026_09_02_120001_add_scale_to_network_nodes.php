<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Индивидуальный масштаб узла — независимо от общего зума рабочей области
// (запрошено в баг-репорте: «изменение масштаба рабочей области и каждого
// объекта отдельно»).
return new class extends Migration {
    public function up(): void
    {
        Schema::table("network_nodes", function (Blueprint $table) {
            $table->decimal("scale", 3, 2)->default(1.00)->after("pos_y");
        });
    }

    public function down(): void
    {
        Schema::table("network_nodes", function (Blueprint $table) {
            $table->dropColumn("scale");
        });
    }
};
