<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Публичная подача заявок сделала роль «Пользователь» (slug: user) лишней.
 * Удаляем такие аккаунты вместе с их заявками и комментариями, затем саму
 * роль. Операция необратима по данным — down() лишь восстанавливает роль.
 */
return new class extends Migration
{
    public function up(): void
    {
        $role = DB::table('roles')->where('slug', 'user')->first();

        if (!$role) {
            return;
        }

        $userIds = DB::table('users')->where('role_id', $role->id)->pluck('id');

        if ($userIds->isNotEmpty()) {
            $ticketIds = DB::table('tickets')
                ->whereIn('user_id', $userIds)
                ->pluck('id');

            if ($ticketIds->isNotEmpty()) {
                DB::table('ticket_comments')
                    ->whereIn('ticket_id', $ticketIds)
                    ->delete();
            }

            // Комментарии этих пользователей к любым заявкам.
            DB::table('ticket_comments')->whereIn('user_id', $userIds)->delete();

            // Заявки удаляем напрямую — минуя мягкое удаление, безвозвратно.
            DB::table('tickets')->whereIn('user_id', $userIds)->delete();

            DB::table('users')->whereIn('id', $userIds)->delete();
        }

        DB::table('roles')->where('id', $role->id)->delete();
    }

    public function down(): void
    {
        // Удалённые аккаунты и заявки восстановлению не подлежат;
        // возвращаем только саму роль ради обратимости схемы.
        DB::table('roles')->updateOrInsert(
            ['slug' => 'user'],
            [
                'name' => 'Пользователь',
                'description' => 'Базовый доступ',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }
};
