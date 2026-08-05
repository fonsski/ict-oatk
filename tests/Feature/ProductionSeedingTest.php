<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\StaffUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductionSeedingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (["admin", "master", "technician"] as $slug) {
            Role::firstOrCreate(["slug" => $slug], ["name" => ucfirst($slug)]);
        }
    }

    private function withStaffConfig(array $members, ?string $password = null): void
    {
        config([
            "staff.members" => $members,
            "staff.default_password" => $password,
        ]);
    }

    public function test_staff_are_created_with_their_role_and_position(): void
    {
        $this->withStaffConfig([
            [
                "name" => "Хоробров Владислав Дмитриевич",
                "position" => "Заведующий мастерскими",
                "role" => "master",
                "phone" => "+79001112233",
            ],
            [
                "name" => "Синегубов Вячеслав Александрович",
                "position" => "Техник первой категории",
                "role" => "technician",
                "phone" => "+79002223344",
            ],
        ], "secret-password");

        $this->seed(StaffUserSeeder::class);

        $master = User::where("phone", "+79001112233")->first();
        $this->assertSame("Заведующий мастерскими", $master->position);
        $this->assertSame("master", $master->role->slug);
        $this->assertTrue($master->is_active);

        $technician = User::where("phone", "+79002223344")->first();
        $this->assertSame("technician", $technician->role->slug);
    }

    /**
     * Вход в систему идёт по телефону, поэтому номер приводится к единому
     * виду — иначе сотрудник, записанный как «8 (900) ...», не войдёт.
     */
    public function test_phone_numbers_are_normalised_to_a_single_format(): void
    {
        $this->withStaffConfig([
            ["name" => "А", "role" => "technician", "phone" => "8 (900) 222-33-44"],
            ["name" => "Б", "role" => "technician", "phone" => "9003334455"],
            ["name" => "В", "role" => "technician", "phone" => "+7 900 444 55 66"],
        ], "pass");

        $this->seed(StaffUserSeeder::class);

        $this->assertSame("+79002223344", User::where("name", "А")->value("phone"));
        $this->assertSame("+79003334455", User::where("name", "Б")->value("phone"));
        $this->assertSame("+79004445566", User::where("name", "В")->value("phone"));
    }

    public function test_member_without_a_phone_is_skipped_rather_than_half_created(): void
    {
        $this->withStaffConfig([
            ["name" => "Без телефона", "role" => "technician", "phone" => null],
        ]);

        $this->seed(StaffUserSeeder::class);

        $this->assertSame(0, User::count());
    }

    /**
     * Повторный деплой не должен сбрасывать пароли работающим людям.
     */
    public function test_reseeding_keeps_existing_passwords(): void
    {
        $this->withStaffConfig([
            ["name" => "Тест", "position" => "Техник", "role" => "technician", "phone" => "+79001112233"],
        ], "first-password");

        $this->seed(StaffUserSeeder::class);
        $originalHash = User::where("phone", "+79001112233")->value("password");

        // Человек сменил пароль сам — следующий деплой не должен это затереть.
        User::where("phone", "+79001112233")->update([
            "password" => Hash::make("changed-by-user"),
        ]);
        $userHash = User::where("phone", "+79001112233")->value("password");

        $this->withStaffConfig([
            ["name" => "Тест", "position" => "Техник", "role" => "technician", "phone" => "+79001112233"],
        ], "first-password");
        $this->seed(StaffUserSeeder::class);

        $this->assertSame($userHash, User::where("phone", "+79001112233")->value("password"));
        $this->assertNotSame($originalHash, User::where("phone", "+79001112233")->value("password"));
        $this->assertSame(1, User::count());
    }

    public function test_reseeding_updates_role_and_position_without_duplicating(): void
    {
        $this->withStaffConfig([
            ["name" => "Тест", "position" => "Техник", "role" => "technician", "phone" => "+79001112233"],
        ], "pass");
        $this->seed(StaffUserSeeder::class);

        // Человека повысили — новый деплой должен подтянуть роль и должность.
        $this->withStaffConfig([
            ["name" => "Тест", "position" => "Заведующий мастерскими", "role" => "master", "phone" => "+79001112233"],
        ], "pass");
        $this->seed(StaffUserSeeder::class);

        $user = User::where("phone", "+79001112233")->first();
        $this->assertSame(1, User::count());
        $this->assertSame("master", $user->role->slug);
        $this->assertSame("Заведующий мастерскими", $user->position);
    }

    /**
     * Главное правило боевого наполнения: на рабочем сервере не должно
     * появиться ни тестовых пользователей, ни показательных закупок.
     */
    public function test_database_seeder_skips_demo_data_in_production(): void
    {
        $seeder = new \Database\Seeders\DatabaseSeeder();
        $source = file_get_contents(
            (new \ReflectionClass($seeder))->getFileName(),
        );

        $this->assertStringContainsString("ProductionSeeder::class", $source);
        $this->assertStringContainsString('environment("production")', $source);

        // Демо-сидеры обязаны быть за проверкой окружения, а не до неё.
        $guardPosition = strpos($source, 'environment("production")');
        foreach (["SampleUsersSeeder", "SupplyDemoSeeder", "HomepageFAQSeeder"] as $demoSeeder) {
            $this->assertGreaterThan(
                $guardPosition,
                strpos($source, $demoSeeder),
                "{$demoSeeder} должен вызываться только вне production",
            );
        }
    }

    public function test_production_seeder_contains_only_operational_data(): void
    {
        $source = file_get_contents(
            (new \ReflectionClass(\Database\Seeders\ProductionSeeder::class))->getFileName(),
        );

        foreach (["RoleSeeder", "InventorySeeder", "StaffUserSeeder"] as $expected) {
            $this->assertStringContainsString($expected, $source);
        }

        foreach (["SampleUsersSeeder", "SupplyDemoSeeder", "DemoShowcaseSeeder"] as $demoSeeder) {
            $this->assertStringNotContainsString($demoSeeder, $source);
        }
    }
}
