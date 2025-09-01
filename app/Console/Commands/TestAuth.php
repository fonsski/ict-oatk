<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class TestAuth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth:test {phone} {password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Тестирование авторизации по номеру телефона и паролю';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $phone = $this->argument('phone');
        $password = $this->argument('password');

        $this->info("Тестирование авторизации для телефона: {$phone}");
        $this->info("Пароль: " . str_repeat('*', strlen($password)));

        // Очищаем номер телефона от форматирования
        $cleanPhone = preg_replace("/[^0-9]/", "", $phone);
        $this->info("Очищенный номер телефона: {$cleanPhone}");

        // Ищем пользователя разными способами
        $this->info("Поиск пользователя в базе данных...");

        // Вариант 1: Точное совпадение
        $user1 = User::where('phone', $cleanPhone)->first();
        if ($user1) {
            $this->info("Найден пользователь (точное совпадение): " . $user1->name . " (ID: " . $user1->id . ")");
            $this->info("Номер в базе: " . $user1->phone);
        } else {
            $this->info("Пользователь не найден при точном совпадении.");
        }

        // Вариант 2: Частичное совпадение
        $user2 = User::where('phone', 'like', "%{$cleanPhone}%")->first();
        if ($user2) {
            $this->info("Найден пользователь (частичное совпадение): " . $user2->name . " (ID: " . $user2->id . ")");
            $this->info("Номер в базе: " . $user2->phone);
        } else {
            $this->info("Пользователь не найден при частичном совпадении.");
        }

        // Вариант 3: Последние 10 цифр
        $user3 = User::where('phone', 'like', "%" . substr($cleanPhone, -10) . "%")->first();
        if ($user3) {
            $this->info("Найден пользователь (по последним 10 цифрам): " . $user3->name . " (ID: " . $user3->id . ")");
            $this->info("Номер в базе: " . $user3->phone);
        } else {
            $this->info("Пользователь не найден по последним 10 цифрам.");
        }

        // Проверяем авторизацию через метод Auth::attempt
        $this->info("\nПроверка авторизации через Auth::attempt:");

        $result1 = Auth::attempt(['phone' => $cleanPhone, 'password' => $password]);
        $this->info("Auth::attempt с точным номером: " . ($result1 ? 'Успешно' : 'Неудачно'));

        // Проверяем авторизацию напрямую через хеш
        $this->info("\nПроверка авторизации напрямую через Hash::check:");

        // Пробуем с user1
        if ($user1) {
            $hash1 = Hash::check($password, $user1->password);
            $this->info("Hash::check для user1: " . ($hash1 ? 'Успешно' : 'Неудачно'));
        }

        // Пробуем с user2
        if ($user2) {
            $hash2 = Hash::check($password, $user2->password);
            $this->info("Hash::check для user2: " . ($hash2 ? 'Успешно' : 'Неудачно'));
        }

        // Пробуем с user3
        if ($user3) {
            $hash3 = Hash::check($password, $user3->password);
            $this->info("Hash::check для user3: " . ($hash3 ? 'Успешно' : 'Неудачно'));
        }

        // Информация о хеше пароля
        if ($user1 || $user2 || $user3) {
            $user = $user1 ?? $user2 ?? $user3;
            $this->info("\nИнформация о хеше пароля:");
            $this->info("Длина хеша: " . strlen($user->password));
            $this->info("Начало хеша: " . substr($user->password, 0, 20) . "...");
            $this->info("Алгоритм: " . (strpos($user->password, '$2y$') === 0 ? 'bcrypt' : 'unknown'));
        }

        // Вывод итогового результата
        $this->info("\nИтоговый результат:");
        $success = ($user1 && Hash::check($password, $user1->password)) ||
                  ($user2 && Hash::check($password, $user2->password)) ||
                  ($user3 && Hash::check($password, $user3->password));

        if ($success) {
            $this->info("✅ Авторизация возможна для введенных учетных данных");
        } else {
            $this->error("❌ Авторизация невозможна для введенных учетных данных");
        }
    }
}
