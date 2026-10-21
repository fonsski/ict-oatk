<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Адреса быстрых действий на странице «Все заявки».
 *
 * Раньше id заявки подставлялся так: route(..., ['ticket' => 0]) и
 * .replace('0', id). route() отдаёт абсолютный адрес, а replace меняет
 * первый ноль в строке — им мог оказаться ноль в порту или IP-адресе
 * (http://localhost:8000/...), и запрос уходил на несуществующий адрес.
 */
class TicketListRouteUrlsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    public function test_ticket_id_is_substituted_by_placeholder_not_by_zero(): void
    {
        $master = User::factory()->withRole('master')->create();

        $this->actingAs($master)
            ->get(route('all-tickets.index'), ['HTTP_HOST' => '10.0.0.5:8000'])
            ->assertOk()
            ->assertSee('/api/tickets/__ID__/status', false)
            ->assertSee('/api/tickets/__ID__/assign', false)
            ->assertDontSee('/api/tickets/0/', false)
            ->assertDontSee(".replace('0'", false);
    }
}
