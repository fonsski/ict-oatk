<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Заводит учётные записи сотрудников отдела из config/staff.php.
 *
 * Идемпотентен: сотрудника ищем по телефону (это логин) и, если он уже
 * заведён, пароль не трогаем — иначе повторный деплой сбрасывал бы пароли
 * работающим людям.
 */
class StaffUserSeeder extends Seeder
{
    public function run(): void
    {
        $members = config("staff.members", []);
        $defaultPassword = config("staff.default_password");

        $created = [];
        $skipped = [];
        $missingPhone = [];

        foreach ($members as $member) {
            $phone = $this->normalizePhone($member["phone"] ?? null);

            if (!$phone) {
                $missingPhone[] = $member["name"];
                continue;
            }

            $role = Role::where("slug", $member["role"])->first();

            if (!$role) {
                $this->command?->error(
                    "Роль «{$member["role"]}» не найдена — сначала выполните RoleSeeder",
                );
                continue;
            }

            $existing = User::where("phone", $phone)->first();

            if ($existing) {
                // Должность и роль подтягиваем, пароль оставляем как есть.
                $existing->update([
                    "name" => $member["name"],
                    "position" => $member["position"] ?? null,
                    "role_id" => $role->id,
                    "is_active" => true,
                ]);
                $skipped[] = $member["name"];
                continue;
            }

            $password = $defaultPassword ?: Str::password(12, symbols: false);

            User::create([
                "name" => $member["name"],
                "position" => $member["position"] ?? null,
                "phone" => $phone,
                "email" => $member["email"] ?? null,
                "password" => Hash::make($password),
                "role_id" => $role->id,
                "is_active" => true,
            ]);

            $created[$member["name"]] = ["phone" => $phone, "password" => $password];
        }

        $this->report($created, $skipped, $missingPhone);
    }

    /**
     * Приводим номер к виду +7XXXXXXXXXX — в этом формате его ждёт вход.
     */
    private function normalizePhone(?string $phone): ?string
    {
        // Тот же вид, что и у номеров, заведённых через интерфейс.
        return normalize_phone($phone);
    }

    private function report(array $created, array $skipped, array $missingPhone): void
    {
        if (!$this->command) {
            return;
        }

        if ($created) {
            $this->command->info("Созданы учётные записи (пароли показываются один раз):");
            $this->command->table(
                ["Сотрудник", "Телефон (логин)", "Пароль"],
                collect($created)
                    ->map(fn($data, $name) => [$name, $data["phone"], $data["password"]])
                    ->values()
                    ->all(),
            );
            $this->command->warn("Сохраните пароли и попросите сотрудников сменить их после первого входа.");
        }

        if ($skipped) {
            $this->command->line(
                "Уже существовали, пароль не менялся: " . implode(", ", $skipped),
            );
        }

        if ($missingPhone) {
            $this->command->error(
                "Не заведены — нет телефона: " . implode(", ", $missingPhone),
            );

            // Самая частая причина на боевом сервере: телефоны в .env уже
            // вписаны, но конфигурация закэширована. При закэшированном
            // конфиге Laravel вообще не читает .env, поэтому сюда приходят
            // старые (пустые) значения, и подсказка «заполните .env»
            // отправляет чинить то, что уже сделано.
            if (app()->configurationIsCached()) {
                $this->command->warn(
                    "Конфигурация закэширована, поэтому правки в .env пока не видны.",
                );
                $this->command->line("Выполните по порядку:");
                $this->command->line("  php artisan config:cache");
                $this->command->line("  php artisan db:seed --class=StaffUserSeeder --force");
            } else {
                $this->command->line(
                    "Заполните переменные STAFF_*_PHONE в .env и повторите: php artisan db:seed --class=StaffUserSeeder --force",
                );
            }
        }
    }
}
