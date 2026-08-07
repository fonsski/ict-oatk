<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use App\Support\GuestTicketOwner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Гость видит и правит свои обращения.
 *
 * При подаче заявки без входа посетителю выдаётся долгоживущая cookie со
 * случайной меткой; та же метка пишется в заявку. Раньше обращение
 * пропадало из виду сразу после отправки, и вместо исправления опечатки
 * человек заводил вторую заявку.
 */
class GuestTicketOwnershipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            "title" => "Не включается монитор",
            "description" => "Монитор не подаёт признаков жизни.",
            "category" => "hardware",
            "priority" => "medium",
            "reporter_name" => "Петров Пётр",
            "reporter_phone" => "+7 (912) 345-67-89",
        ], $overrides);
    }

    /**
     * Метка из выданной cookie. Берём расшифрованной: withCookie() в
     * тестах шифрует значение сам, как это сделал бы браузер.
     */
    private function tokenFrom($response): string
    {
        return $response->getCookie(GuestTicketOwner::COOKIE)->getValue();
    }

    public function test_submitting_issues_a_cookie_and_marks_the_ticket(): void
    {
        $response = $this->post(route("tickets.store"), $this->payload());

        $token = $this->tokenFrom($response);

        $this->assertSame(64, strlen($token));
        $this->assertSame($token, Ticket::first()->guest_token);
    }

    public function test_guest_sees_only_their_own_tickets(): void
    {
        $mine = $this->tokenFrom(
            $this->post(route("tickets.store"), $this->payload([
                "title" => "Моя заявка",
            ])),
        );

        // Чужая заявка с другой меткой.
        Ticket::create($this->payload(["title" => "Чужая заявка"]) + [
            "status" => "open",
        ])->forceFill(["guest_token" => Str::random(64)])->save();

        $titles = $this->withCookie(GuestTicketOwner::COOKIE, $mine)
            ->get(route("tickets.index"))
            ->assertOk()
            ->viewData("tickets")
            ->pluck("title");

        $this->assertTrue($titles->contains("Моя заявка"));
        $this->assertFalse($titles->contains("Чужая заявка"));
    }

    public function test_second_ticket_reuses_the_same_token(): void
    {
        $first = $this->tokenFrom(
            $this->post(route("tickets.store"), $this->payload()),
        );

        $this->withCookie(GuestTicketOwner::COOKIE, $first)
            ->post(route("tickets.store"), $this->payload(["title" => "Вторая"]));

        $this->assertSame(2, Ticket::count());
        $this->assertSame(
            1,
            Ticket::distinct()->pluck("guest_token")->count(),
            "Обе заявки должны остаться за одним гостем",
        );
    }

    public function test_guest_can_open_their_own_ticket(): void
    {
        $token = $this->tokenFrom(
            $this->post(route("tickets.store"), $this->payload()),
        );

        $this->withCookie(GuestTicketOwner::COOKIE, $token)
            ->get(route("tickets.show", Ticket::first()))
            ->assertOk()
            ->assertSee("Не включается монитор");
    }

    public function test_guest_cannot_open_a_ticket_with_a_foreign_token(): void
    {
        $this->post(route("tickets.store"), $this->payload());

        $this->withCookie(GuestTicketOwner::COOKIE, Str::random(64))
            ->get(route("tickets.show", Ticket::first()))
            ->assertForbidden();
    }

    public function test_guest_can_correct_their_own_ticket(): void
    {
        $token = $this->tokenFrom(
            $this->post(route("tickets.store"), $this->payload([
                "title" => "Не влючается манитор",
            ])),
        );
        $ticket = Ticket::first();

        $this->withCookie(GuestTicketOwner::COOKIE, $token)
            ->put(route("tickets.update", $ticket), $this->payload([
                "title" => "Не включается монитор",
                "description" => "Исправленное описание.",
            ]))
            ->assertRedirect();

        $ticket->refresh();
        $this->assertSame("Не включается монитор", $ticket->title);
        $this->assertSame("Исправленное описание.", $ticket->description);
    }

    public function test_guest_cannot_edit_a_foreign_ticket(): void
    {
        $this->post(route("tickets.store"), $this->payload());
        $ticket = Ticket::first();

        $this->withCookie(GuestTicketOwner::COOKIE, Str::random(64))
            ->put(route("tickets.update", $ticket), $this->payload([
                "title" => "Взломано",
            ]))
            ->assertForbidden();

        $this->assertSame("Не включается монитор", $ticket->refresh()->title);
    }

    /**
     * Пока заявку не взяли в работу — правим. После этого текст фиксируется,
     * иначе исполнитель чинит одно, а в заявке написано другое.
     */
    public function test_editing_stops_once_the_ticket_is_taken(): void
    {
        $token = $this->tokenFrom(
            $this->post(route("tickets.store"), $this->payload()),
        );
        $ticket = Ticket::first();
        $ticket->update(["status" => "in_progress"]);

        $this->withCookie(GuestTicketOwner::COOKIE, $token)
            ->get(route("tickets.edit", $ticket))
            ->assertForbidden();
    }

    /**
     * Автор правит текст обращения, но не служебные поля — даже если
     * подставит их в запрос руками.
     */
    public function test_guest_cannot_change_status_or_assignee(): void
    {
        $technician = User::factory()->withRole("technician")->create();
        $token = $this->tokenFrom(
            $this->post(route("tickets.store"), $this->payload()),
        );
        $ticket = Ticket::first();

        $this->withCookie(GuestTicketOwner::COOKIE, $token)->put(
            route("tickets.update", $ticket),
            $this->payload([
                "title" => "Поправленный заголовок",
                "status" => "closed",
                "assigned_to_id" => $technician->id,
            ]),
        );

        $ticket->refresh();
        $this->assertSame("Поправленный заголовок", $ticket->title);
        $this->assertSame("open", $ticket->status);
        $this->assertNull($ticket->assigned_to_id);
    }

    public function test_token_is_never_exposed_in_serialised_output(): void
    {
        $this->post(route("tickets.store"), $this->payload());

        $this->assertArrayNotHasKey("guest_token", Ticket::first()->toArray());
    }
}
