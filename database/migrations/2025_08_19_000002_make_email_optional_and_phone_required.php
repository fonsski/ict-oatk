<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class MakeEmailOptionalAndPhoneRequired extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Сначала удаляем уникальное ограничение с email
        Schema::table('users', function (Blueprint $table) {
            // Для MySQL
            if (DB::getDriverName() === 'mysql') {
                $table->dropIndex('users_email_unique');
            }
            // Для PostgreSQL
            else if (DB::getDriverName() === 'pgsql') {
                $table->dropUnique('users_email_unique');
            }
            // Для SQLite
            else {
                $table->dropUnique(['email']);
            }
        });

        // Делаем email nullable
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });

        // Добавляем уникальное ограничение для телефона, если его еще нет
        if (!$this->hasUniqueIndex('users', 'phone')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('phone');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Удаляем уникальное ограничение с телефона
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone']);
        });

        // Возвращаем обязательность и уникальность email
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
            $table->unique('email');
        });
    }

    /**
     * Проверяет наличие уникального индекса для указанного столбца
     */
    private function hasUniqueIndex($table, $column)
    {
        try {
            $indexes = DB::select("SHOW INDEXES FROM {$table} WHERE Column_name = '{$column}' AND Non_unique = 0");
            return count($indexes) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
}
