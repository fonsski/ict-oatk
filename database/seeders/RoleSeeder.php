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
                'slug' => 'technician',
                'description' => 'Работа с заявками'
            ]
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
