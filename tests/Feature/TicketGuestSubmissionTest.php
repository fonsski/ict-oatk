<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Публичная подача заявок: гость оставляет заявку без входа, указывая ФИО
 * и телефон; вошедший сотрудник подаёт заявку от своего имени.
 */
class TicketGuestSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Не работает проектор',
            'category' => 'hardware',
            'priority' => 'medium',
            'description' => 'Проектор в аудитории 305 не включается со вчерашнего дня.',
            'reporter_name' => 'Петров Пётр Петрович',
            'reporter_phone' => '+7 (912) 345-67-89',
            'reporter_email' => 'petrov@example.com',
        ], $overrides);
    }

    public function test_guest_can_open_the_submission_form(): void
    {
        $this->get(route('tickets.create'))
            ->assertOk()
            ->assertSee('ФИО', false);
    }

    public function test_guest_can_submit_a_ticket(): void
    {
        $response = $this->post(route('tickets.store'), $this->validPayload());

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('success');

        $ticket = Ticket::first();
        $this->assertNotNull($ticket);
        $this->assertNull($ticket->user_id);
        $this->assertSame('Петров Пётр Петрович', $ticket->reporter_name);
        // Телефон сохраняется нормализованным.
        $this->assertSame('+79123456789', $ticket->reporter_phone);
        $this->assertSame('petrov@example.com', $ticket->reporter_email);
    }

    public function test_guest_submission_requires_name_and_phone(): void
    {
        $response = $this->post(route('tickets.store'), $this->validPayload([
            'reporter_name' => '',
            'reporter_phone' => '',
        ]));

        $response->assertSessionHasErrors(['reporter_name', 'reporter_phone']);
        $this->assertSame(0, Ticket::count());
    }

    public function test_guest_submission_rejects_malformed_phone(): void
    {
        $response = $this->post(route('tickets.store'), $this->validPayload([
            'reporter_phone' => '1234',
        ]));

        $response->assertSessionHasErrors('reporter_phone');
        $this->assertSame(0, Ticket::count());
    }

    public function test_staff_submission_uses_account_details(): void
    {
        $technician = User::factory()->withRole('technician')->create([
            'name' => 'Сидоров Сидор',
            'phone' => '+79997776655',
        ]);

        // Сотрудник мог бы подсунуть чужие данные — они должны игнорироваться.
        $this->actingAs($technician)->post(route('tickets.store'), $this->validPayload([
            'reporter_name' => 'Чужое Имя',
            'reporter_phone' => '+7 (000) 000-00-00',
        ]));

        $ticket = Ticket::first();
        $this->assertSame($technician->id, $ticket->user_id);
        $this->assertSame('Сидоров Сидор', $ticket->reporter_name);
        $this->assertSame('+79997776655', $ticket->reporter_phone);
    }

    public function test_staff_submission_redirects_to_ticket(): void
    {
        $technician = User::factory()->withRole('technician')->create();

        $response = $this->actingAs($technician)
            ->post(route('tickets.store'), $this->validPayload());

        $ticket = Ticket::first();
        $response->assertRedirect(route('tickets.show', $ticket));
    }

    public function test_staff_form_does_not_ask_for_reporter_fields(): void
    {
        $technician = User::factory()->withRole('technician')->create();

        $this->actingAs($technician)
            ->get(route('tickets.create'))
            ->assertOk()
            ->assertDontSee('name="reporter_name"', false);
    }

    public function test_submission_is_rate_limited(): void
    {
        // Публичный маршрут ограничен throttle:5,1 — шестая заявка отклоняется.
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('tickets.store'), $this->validPayload())
                ->assertRedirect(route('home'));
        }

        $this->post(route('tickets.store'), $this->validPayload())
            ->assertStatus(429);

        $this->assertSame(5, Ticket::count());
    }
}
