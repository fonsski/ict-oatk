<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Сначала изменяем поле phone, делая его необязательным
        Schema::table("users", function (Blueprint $table) {
            $table->string("phone")->nullable()->change();
        });

        // Обновляем существующих пользователей, у которых email пустой, но есть телефон
        $users = DB::table("users")
            ->whereNull("email")
            ->whereNotNull("phone")
            ->get();

        foreach ($users as $user) {
            // Создаем временный email на основе телефона
            $tempEmail =
                "user_" .
                preg_replace("/[^0-9]/", "", $user->phone) .
                "@temp.domain";

            DB::table("users")
                ->where("id", $user->id)
                ->update(["email" => $tempEmail]);
        }

        // Добавляем индекс для email, если его нет
        Schema::table("users", function (Blueprint $table) {
            if (!Schema::hasIndex("users", "users_email_index")) {
                $table->index("email", "users_email_index");
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Удаляем индекс, если он был добавлен
        Schema::table("users", function (Blueprint $table) {
            if (Schema::hasIndex("users", "users_email_index")) {
                $table->dropIndex("users_email_index");
            }
        });

        // Возвращаем поле phone как обязательное, если было раньше
        Schema::table("users", function (Blueprint $table) {
            $table->string("phone")->nullable(false)->change();
        });
    }
};
