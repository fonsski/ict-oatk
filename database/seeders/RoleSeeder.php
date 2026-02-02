<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            [
                'name' => 'Администратор',
                'slug' => 'admin',
                'description' => 'Полный доступ к системе'
            ],
            [
                'name' => 'Мастер',
                'slug' => 'master',
                'description' => 'Управление оборудованием и заявками'
            ],
            [
                'name' => 'Техник',
                'slug' => 'technican',
                'description' => 'Работа с заявками'
            ],
            [
                'name' => 'Пользователь',
                'slug' => 'user',
                'description' => 'Базовый доступ'
            ]
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
