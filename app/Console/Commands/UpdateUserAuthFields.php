<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateUserAuthFields extends Command
{
    
     * The name and signature of the console command.
     *
     * @var string

    protected $signature = "users:update-auth-fields";

    
     * The console command description.
     *
     * @var string

    protected $description = "Обновление пользователей для поддержки аутентификации по телефону";

    
     * Execute the console command.

    public function handle()
    {
        $this->info("Начало обновления полей аутентификации пользователей...");

        
        if (!Schema::hasColumn("users", "phone")) {
            $this->error(
                'Поле "phone" не существует в таблице users. Сначала выполните миграцию.',
            );
            return 1;
        }

        try {
            
            DB::beginTransaction();

            
            $usersWithoutPhone = User::whereNull("phone")->get();
            $count = $usersWithoutPhone->count();

            if ($count > 0) {
                $this->info(
                    "Найдено {$count} пользователей без номера телефона.",
                );

                $bar = $this->output->createProgressBar($count);
                $bar->start();

                foreach ($usersWithoutPhone as $user) {
                    
                    $phone = "+7" . str_pad($user->id, 10, "0", STR_PAD_LEFT);

                    
                    $user->phone = $phone;
                    $user->save();

                    $bar->advance();
                }

                $bar->finish();
                $this->newLine();
                $this->info("Обновление завершено успешно.");
            } else {
                $this->info("Все пользователи уже имеют номера телефонов.");
            }

            
            DB::commit();

            
            if (!$this->hasIndex("users", "phone")) {
                $this->info("Создание индекса для поля phone...");
                Schema::table("users", function ($table) {
                    $table->index("phone");
                });
                $this->info("Индекс создан успешно.");
            }

            $this->info("Обновление полей аутентификации завершено успешно!");
            return 0;
        } catch (\Exception $e) {
            
            DB::rollBack();
            $this->error("Произошла ошибка: " . $e->getMessage());
            return 1;
        }
    }

    
     * Проверяет, существует ли индекс для указанного поля
     *
     * @param string $table
     * @param string $column
     * @return bool

    protected function hasIndex($table, $column)
    {
        try {
            
            $indexes = DB::select(
                "SHOW INDEX FROM {$table} WHERE Column_name = '{$column}'",
            );
            return count($indexes) > 0;
        } catch (\Exception $e) {
            $this->warn("Не удалось проверить индекс: " . $e->getMessage());
            
            return false;
        }
    }
}
