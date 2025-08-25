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
            'name' => 'Хоробров Владислав Дмитриевич',
            'phone' => '+79953940601',
            'password' => Hash::make('admin123'),
            'role_id' => $adminRole->id,
        ]);
    }
}