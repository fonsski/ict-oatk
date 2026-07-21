<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddReporterPhoneToTicketsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table("tickets", function (Blueprint $table) {
            // Check if reporter_email column exists first
            if (
                Schema::hasColumn("tickets", "reporter_email") &&
                !Schema::hasColumn("tickets", "reporter_phone")
            ) {
                $table
                    ->string("reporter_phone")
                    ->nullable()
                    ->after("reporter_email");
            } elseif (!Schema::hasColumn("tickets", "reporter_phone")) {
                // If reporter_email doesn't exist, add after status
                $table->string("reporter_phone")->nullable()->after("status");
            }
        });

        // Попытка заполнить новое поле reporter_phone данными из связанных пользователей
        $this->updateReporterPhones();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("tickets", function (Blueprint $table) {
            if (Schema::hasColumn("tickets", "reporter_phone")) {
                $table->dropColumn("reporter_phone");
            }
        });
    }

    /**
     * Обновляет номера телефонов в заявках на основе данных пользователей
     */
    private function updateReporterPhones(): void
    {
        try {
            $hasEmailColumn = Schema::hasColumn("tickets", "reporter_email");

            // Портируемый вариант (MySQL и SQLite не поддерживают UPDATE ... JOIN
            // одинаково): проходим по пользователям с телефоном и обновляем заявки.
            DB::table("users")
                ->whereNotNull("phone")
                ->select("id", "email", "phone")
                ->orderBy("id")
                ->chunk(500, function ($users) use ($hasEmailColumn) {
                    foreach ($users as $user) {
                        DB::table("tickets")
                            ->whereNull("reporter_phone")
                            ->where("user_id", $user->id)
                            ->update(["reporter_phone" => $user->phone]);

                        if ($hasEmailColumn && $user->email) {
                            DB::table("tickets")
                                ->whereNull("reporter_phone")
                                ->where("reporter_email", $user->email)
                                ->update(["reporter_phone" => $user->phone]);
                        }
                    }
                });
        } catch (\Exception $e) {
            error_log(
                "Ошибка при обновлении телефонов в заявках: " .
                    $e->getMessage(),
            );
        }
    }
}
