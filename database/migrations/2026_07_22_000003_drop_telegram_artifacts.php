<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Telegram-интеграция удалена целиком. Убираем её следы из схемы:
 * колонку users.telegram_id и таблицу sent_telegram_notifications.
 * Проверки делают миграцию безопасной как для существующих баз,
 * так и для свежих (где этих объектов уже нет).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('sent_telegram_notifications');

        if (Schema::hasColumn('users', 'telegram_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('telegram_id');
            });
        }
    }

    public function down(): void
    {
        // Интеграция удалена без возможности восстановления; возвращаем
        // только колонку, чтобы миграция была обратимой по схеме.
        if (!Schema::hasColumn('users', 'telegram_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('telegram_id')->nullable()->after('phone');
            });
        }
    }
};
