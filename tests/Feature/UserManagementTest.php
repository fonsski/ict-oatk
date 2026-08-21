<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Страница управления пользователями: заведение учётных записей, выгрузка
 * и сводка.
 */
class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    private function master(): User
    {
        return User::factory()->withRole("master")->create();
    }

    private function technicianRoleId(): int
    {
        return Role::where("slug", "technician")->value("id");
    }

    /**
     * Ровно то, что отправляет форма создания. Раньше запрос требовал ещё и
     * email, которого в форме нет, — завести человека было невозможно.
     */
    public function test_a_user_can_be_created_from_the_form(): void
    {
        $response = $this->actingAs($this->master())->post(route("user.store"), [
            "name" => "Синегубов Вячеслав Александрович",
            "position" => "Техник первой категории",
            "phone" => "+7 (900) 123-45-67",
            "role_id" => $this->technicianRoleId(),
            "password" => "Parol12345",
            "password_confirmation" => "Parol12345",
            "is_active" => "1",
        ]);

        $response->assertRedirect(route("user.index"));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas("users", [
            "name" => "Синегубов Вячеслав Александрович",
            "position" => "Техник первой категории",
            "phone" => "+79001234567",
        ]);
    }

    /**
     * Телефон — это логин, и хранить его надо в том же виде, в каком его
     * потом ищет вход в систему. Иначе человек не сможет войти под своим
     * же номером, набранным чуть иначе.
     */
    public function test_phone_is_stored_in_one_form_whatever_was_typed(): void
    {
        $master = $this->master();

        foreach (
            [
                "8 (900) 111-22-33" => "+79001112233",
                "+7 900 111 22 44" => "+79001112244",
                "9001112255" => "+79001112255",
            ]
            as $typed => $stored
        ) {
            $this->actingAs($master)->post(route("user.store"), [
                "name" => "Сотрудник {$stored}",
                "phone" => $typed,
                "role_id" => $this->technicianRoleId(),
                "password" => "Parol12345",
                "password_confirmation" => "Parol12345",
            ]);

            $this->assertDatabaseHas("users", ["phone" => $stored]);
        }
    }

    /**
     * Номер занят — значит занят, как бы его ни набрали. Проверка
     * уникальности сравнивала введённое с нормализованным в базе и
     * пропускала двойников.
     */
    public function test_the_same_phone_cannot_be_taken_twice(): void
    {
        $master = $this->master();

        User::factory()->withRole("technician")->create([
            "phone" => "+79001112233",
        ]);

        $this->actingAs($master)
            ->post(route("user.store"), [
                "name" => "Двойник",
                "phone" => "8 (900) 111-22-33",
                "role_id" => $this->technicianRoleId(),
                "password" => "Parol12345",
                "password_confirmation" => "Parol12345",
            ])
            ->assertSessionHasErrors("phone");

        $this->assertSame(
            1,
            User::where("phone", "+79001112233")->count(),
        );
    }

    /**
     * Почта нужна для восстановления пароля, но она есть не у всех — и в
     * базе колонка пустоту допускает.
     */
    public function test_email_is_optional_but_saved_when_given(): void
    {
        $master = $this->master();

        $this->actingAs($master)->post(route("user.store"), [
            "name" => "С почтой",
            "email" => "ict@oatk.org",
            "phone" => "+79001112266",
            "role_id" => $this->technicianRoleId(),
            "password" => "Parol12345",
            "password_confirmation" => "Parol12345",
        ]);

        $this->assertDatabaseHas("users", [
            "phone" => "+79001112266",
            "email" => "ict@oatk.org",
        ]);
    }

    public function test_editing_saves_email_and_position(): void
    {
        $master = $this->master();
        $user = User::factory()->withRole("technician")->create([
            "phone" => "+79001112277",
        ]);

        $this->actingAs($master)
            ->put(route("user.update", $user), [
                "name" => "Новое имя",
                "position" => "Техник",
                "email" => "tech@oatk.org",
                "phone" => "+79001112277",
                "role_id" => $this->technicianRoleId(),
                "is_active" => "1",
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas("users", [
            "id" => $user->id,
            "name" => "Новое имя",
            "position" => "Техник",
            "email" => "tech@oatk.org",
        ]);
    }

    /**
     * Обе кнопки на странице списка вели в никуда: Route::resource стоял
     * раньше и ловил «export» и «statistics» как имя пользователя.
     */
    public function test_export_and_statistics_pages_are_reachable(): void
    {
        $master = $this->master();

        $this->actingAs($master)->get(route("user.export"))->assertOk();
        $this->actingAs($master)->get(route("user.statistics"))->assertOk();
    }

    public function test_technician_cannot_manage_users(): void
    {
        $technician = User::factory()->withRole("technician")->create();

        $this->actingAs($technician)
            ->get(route("user.index"))
            ->assertForbidden();
    }
}
