<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateRolesTable extends Migration
{
    public function up(): void
    {
        
        DB::table('roles')->insert([
            [
                'name' => 'Администратор',
                'slug' => 'admin',
                'description' => 'Полный доступ к системе',
                'created_at' => now()
            ],
            [
                'name' => 'Техник',
                'slug' => 'technican',
                'description' => 'Работа с заявками',
                'created_at' => now()
            ],
            [
                'name' => 'Мастер',
                'slug' => 'master',
                'description' => 'Управление оборудованием и заявками',
                'created_at' => now()
            ],
            [
                'name' => 'Пользователь',
                'slug' => 'user',
                'description' => 'Базовый доступ',
                'created_at' => now()
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('roles')->truncate();
    }
};
