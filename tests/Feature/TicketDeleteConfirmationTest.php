<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Удаление заявки требует ввода слова подтверждения.
 *
 * Проверка живёт на сервере, а не только в окне подтверждения: иначе
 * удаление ушло бы прямым запросом мимо всякого подтверждения.
 */
class TicketDeleteConfirmationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    private function ticket(): Ticket
    {
        return Ticket::create([
            "title" => "Починить телепорт",
            "description" => "Заявка ради шутки.",
            "category" => "hardware",
            "priority" => "low",
            "status" => "open",
            "reporter_name" => "Иванов Иван",
            "reporter_phone" => "+79001234567",
        ]);
    }

    public function test_master_can_delete_with_the_keyword(): void
    {
        $ticket = $this->ticket();

        $this->actingAs(User::factory()->withRole("master")->create())
            ->delete(route("tickets.destroy", $ticket), [
                "confirmation" => "УДАЛИТЬ",
            ])
            ->assertRedirect();

        $this->assertSoftDeleted("tickets", ["id" => $ticket->id]);
    }

    public function test_lowercase_keyword_is_accepted(): void
    {
        $ticket = $this->ticket();

        $this->actingAs(User::factory()->withRole("admin")->create())
            ->delete(route("tickets.destroy", $ticket), [
                "confirmation" => "удалить",
            ])
            ->assertRedirect();

        $this->assertSoftDeleted("tickets", ["id" => $ticket->id]);
    }

    public function test_deletion_without_the_keyword_is_refused(): void
    {
        $ticket = $this->ticket();

        $this->actingAs(User::factory()->withRole("master")->create())
            ->delete(route("tickets.destroy", $ticket))
            ->assertSessionHasErrors("confirmation");

        $this->assertNotSoftDeleted("tickets", ["id" => $ticket->id]);
    }

    public function test_wrong_keyword_is_refused(): void
    {
        $ticket = $this->ticket();

        $this->actingAs(User::factory()->withRole("master")->create())
            ->delete(route("tickets.destroy", $ticket), [
                "confirmation" => "ок",
            ])
            ->assertSessionHasErrors("confirmation");

        $this->assertNotSoftDeleted("tickets", ["id" => $ticket->id]);
    }

    /**
     * Убирать чужие обращения из системы техник не должен.
     */
    public function test_technician_cannot_delete_tickets(): void
    {
        $ticket = $this->ticket();

        $this->actingAs(User::factory()->withRole("technician")->create())
            ->delete(route("tickets.destroy", $ticket), [
                "confirmation" => "УДАЛИТЬ",
            ])
            ->assertForbidden();

        $this->assertNotSoftDeleted("tickets", ["id" => $ticket->id]);
    }

    public function test_permanent_deletion_also_needs_the_keyword(): void
    {
        $ticket = $this->ticket();
        $ticket->delete();

        $this->actingAs(User::factory()->withRole("admin")->create())
            ->delete(route("tickets.force-delete", $ticket->id))
            ->assertSessionHasErrors("confirmation");

        $this->assertSoftDeleted("tickets", ["id" => $ticket->id]);
    }

    public function test_confirmation_dialog_shows_what_is_being_deleted(): void
    {
        $ticket = $this->ticket();

        $this->actingAs(User::factory()->withRole("master")->create())
            ->get(route("tickets.show", $ticket))
            ->assertOk()
            ->assertSee("Починить телепорт")
            ->assertSee("Заявка ради шутки.")
            ->assertSee("УДАЛИТЬ");
    }
}
