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
            // Проверяем существование столбца reporter_email перед выполнением запроса
            if (Schema::hasColumn("tickets", "reporter_email")) {
                // Обновляем телефоны из связанных пользователей (где reporter_email совпадает с email пользователя)
                DB::statement("
                    UPDATE tickets t
                    JOIN users u ON t.reporter_email = u.email
                    SET t.reporter_phone = u.phone
                    WHERE t.reporter_phone IS NULL
                    AND u.phone IS NOT NULL
                ");
            }

            // Обновляем телефоны из связанных пользователей (где user_id указан)
            DB::statement("
                UPDATE tickets t
                JOIN users u ON t.user_id = u.id
                SET t.reporter_phone = u.phone
                WHERE t.reporter_phone IS NULL
                AND u.phone IS NOT NULL
            ");

            // Завершаем обновление без записи в лог
            return;
        } catch (\Exception $e) {
            // Логируем ошибку в консоль
            error_log(
                "Ошибка при обновлении телефонов в заявках: " .
                    $e->getMessage(),
            );
        }
    }
}
