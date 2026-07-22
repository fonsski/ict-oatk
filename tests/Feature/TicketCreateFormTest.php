<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Форма подачи заявки: блок оборудования показывается только для
 * категории «Оборудование», список кабинетов приходит в разметке.
 */
class TicketCreateFormTest extends TestCase
{
    use RefreshDatabase;

    private function makeRoom(string $number, string $name): Room
    {
        return Room::create([
            'number' => $number,
            'name' => $name,
            'type' => 'classroom',
            'building' => 'Главное здание',
            'floor' => 1,
            'is_active' => true,
        ]);
    }

    public function test_equipment_block_is_visible_by_default(): void
    {
        $user = User::factory()->withRole('user')->create();

        $response = $this->actingAs($user)->get(route('tickets.create'));

        $response->assertOk()->assertSee('id="equipment_block"', false);

        // Категория по умолчанию — hardware, поэтому атрибута hidden нет.
        $this->assertStringNotContainsString(
            'id="equipment_block" hidden',
            $response->getContent(),
        );
    }

    public function test_equipment_block_is_hidden_when_category_is_not_hardware(): void
    {
        $user = User::factory()->withRole('user')->create();

        // Имитируем возврат на форму после ошибки валидации.
        $response = $this->actingAs($user)
            ->withSession(['_old_input' => ['category' => 'software']])
            ->get(route('tickets.create'));

        $response->assertOk()->assertSee('id="equipment_block" hidden', false);
    }

    public function test_room_options_carry_search_attributes(): void
    {
        $user = User::factory()->withRole('user')->create();
        $this->makeRoom('101', 'Компьютерный класс №1');

        $response = $this->actingAs($user)->get(route('tickets.create'));

        // Поиск строит запрос по этим data-атрибутам.
        $response->assertOk()
            ->assertSee('data-number="101"', false)
            ->assertSee('data-name="Компьютерный класс №1"', false);
    }

    public function test_inactive_rooms_are_not_offered(): void
    {
        $user = User::factory()->withRole('user')->create();
        $this->makeRoom('101', 'Активный кабинет');

        $hidden = $this->makeRoom('999', 'Отключённый кабинет');
        $hidden->update(['is_active' => false]);

        $this->actingAs($user)
            ->get(route('tickets.create'))
            ->assertOk()
            ->assertSee('Активный кабинет', false)
            ->assertDontSee('Отключённый кабинет', false);
    }
}
