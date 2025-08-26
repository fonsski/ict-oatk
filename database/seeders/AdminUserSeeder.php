<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where("slug", "=", "admin")->first();

        $user = new User();
        $user->name = "Хоробров Владислав Дмитриевич";
        $user->email = "admin@example.com"; // Добавляем обязательное поле email
        $user->phone = "+79953940601";
        $user->password = Hash::make("admin123");
        $user->role_id = $adminRole->id;
        $user->is_active = true; // Явно указываем, что пользователь активен
        $user->save();
    }
}
