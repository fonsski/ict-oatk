<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Создаёт по одному демонстрационному пользователю на каждую роль
 * (кроме админа — он создаётся в AdminUserSeeder) для ручного тестирования.
 *
 * Учётные данные для входа (вход выполняется по номеру телефона):
 *   Мастер:       +79000000002 / password
 *   Техник:       +79000000003 / password
 *   Пользователь: +79000000004 / password
 */
class SampleUsersSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Мастер Тестовый',
                'phone' => '+79000000002',
                'email' => 'master@example.com',
                'role' => 'master',
            ],
            [
                'name' => 'Техник Тестовый',
                'phone' => '+79000000003',
                'email' => 'technician@example.com',
                'role' => 'technician',
            ],
            [
                'name' => 'Пользователь Тестовый',
                'phone' => '+79000000004',
                'email' => 'user@example.com',
                'role' => 'user',
            ],
        ];

        foreach ($users as $data) {
            $role = Role::where('slug', $data['role'])->first();

            if (!$role) {
                continue;
            }

            User::updateOrCreate(
                ['phone' => $data['phone']],
                [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make('password'),
                    'role_id' => $role->id,
                    'is_active' => true,
                ],
            );
        }
    }
}
