<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * В production наполняем только боевыми данными. Демонстрационные
     * заявки, тестовые пользователи и показательные закупки нужны для
     * разработки и на рабочем сервере лишние — поэтому они за проверкой
     * окружения, а не в общем списке.
     */
    public function run(): void
    {
        $this->call(ProductionSeeder::class);

        if (app()->environment("production")) {
            return;
        }

        $this->call([
            AdminUserSeeder::class,
            SampleUsersSeeder::class,
            HomepageFAQSeeder::class,
            SupplyDemoSeeder::class,
        ]);
    }
}
