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
        $adminRole = Role::where('slug', 'admin')->first();
        
        User::create([
            'name' => 'Администратор',
            'email' => 'admin@ict.local',
            'password' => Hash::make('admin123'),
            'role_id' => $adminRole->id,
        ]);
    }
}