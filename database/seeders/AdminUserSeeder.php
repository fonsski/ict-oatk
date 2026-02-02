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
        if (!$adminRole) {
            
            $adminRole = Role::first();
            if (!$adminRole) {
                
                return;
            }
        }

        
        $existingAdmin = User::where("phone", "+79953940601")->first();
        if ($existingAdmin) {
            
            $existingAdmin->name = "Хоробров Владислав Дмитриевич";
            $existingAdmin->email = "admin@example.com";
            $existingAdmin->password = Hash::make("admin123");
            $existingAdmin->role_id = $adminRole->id;
            $existingAdmin->is_active = true;
            $existingAdmin->save();
            return;
        }

        
        $user = new User();
        $user->name = "Хоробров Владислав Дмитриевич";
        $user->email = "admin@example.com";
        $user->phone = "+79953940601";
        $user->password = Hash::make("admin123");
        $user->role_id = $adminRole->id;
        $user->is_active = true;
        $user->save();
    }
}