<?php

namespace Tests\Feature;

use App\Models\HomepageFAQ;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Баг-репорт техника: счётчики статусов, частые вопросы и меню.
 */
class TechnicianBugReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    private function ticket(User $author, string $status, array $extra = []): Ticket
    {
        return Ticket::create(array_merge([
            'title' => 'Заявка ' . uniqid(),
            'description' => 'Описание не короче десяти символов.',
            'category' => 'hardware',
            'priority' => 'medium',
            'status' => $status,
            'reporter_name' => $author->name,
            'reporter_phone' => $author->phone,
            'user_id' => $author->id,
        ], $extra));
    }

    /** 2 открытые, 1 в работе, 3 решённые, 4 закрытые. */
    private function seedTickets(User $author): void
    {
        foreach (['open' => 2, 'in_progress' => 1, 'resolved' => 3, 'closed' => 4] as $status => $n) {
            for ($i = 0; $i < $n; $i++) {
                $this->ticket($author, $status);
            }
        }
    }

    private const ALL = ['total' => 10, 'open' => 2, 'in_progress' => 1, 'resolved' => 3, 'closed' => 4];

    public function test_all_tickets_counters_ignore_the_status_filter(): void
    {
        $tech = User::factory()->withRole('technician')->create();
        $this->seedTickets($tech);

        // На вкладке «Решённые» остальные карточки не должны обнуляться.
        $this->actingAs($tech)
            ->get(route('all-tickets.index', ['status' => 'resolved']))
            ->assertOk()
            ->assertViewHas('statusCounts', self::ALL);
    }

    public function test_all_tickets_counters_follow_the_other_filters(): void
    {
        $tech = User::factory()->withRole('technician')->create();
        $this->seedTickets($tech);
        $this->ticket($tech, 'open', ['priority' => 'urgent']);

        $this->actingAs($tech)
            ->get(route('all-tickets.index', ['status' => 'closed', 'priority' => 'urgent']))
            ->assertViewHas('statusCounts', [
                'total' => 1, 'open' => 1, 'in_progress' => 0, 'resolved' => 0, 'closed' => 0,
            ]);
    }

    public function test_all_tickets_counters_count_every_page_not_only_the_current_one(): void
    {
        $tech = User::factory()->withRole('technician')->create();
        for ($i = 0; $i < 20; $i++) {
            $this->ticket($tech, 'open');
        }

        $this->actingAs($tech)
            ->get(route('all-tickets.index'))
            ->assertViewHas('statusCounts', [
                'total' => 20, 'open' => 20, 'in_progress' => 0, 'resolved' => 0, 'closed' => 0,
            ]);
    }

    public function test_live_refresh_keeps_the_same_counters(): void
    {
        $tech = User::factory()->withRole('technician')->create();
        $this->seedTickets($tech);

        $response = $this->actingAs($tech)
            ->getJson(route('all-tickets.api', ['status' => 'resolved']))
            ->assertOk();

        $this->assertSame(self::ALL, $response->json('stats'));
        $this->assertCount(3, $response->json('tickets'));
    }

    public function test_unassigned_filter_lists_tickets_without_an_assignee(): void
    {
        $tech = User::factory()->withRole('technician')->create();
        $free = $this->ticket($tech, 'open');
        $this->ticket($tech, 'open', ['assigned_to_id' => $tech->id]);

        $response = $this->actingAs($tech)
            ->getJson(route('all-tickets.api', ['assigned_to' => 'unassigned']));

        $this->assertSame([$free->id], collect($response->json('tickets'))->pluck('id')->all());
    }

    public function test_my_tickets_counters_ignore_the_status_filter(): void
    {
        $tech = User::factory()->withRole('technician')->create();
        $this->seedTickets($tech);
        // Чужая заявка в счётчики не попадает.
        $this->ticket(User::factory()->withRole('technician')->create(), 'resolved');

        $this->actingAs($tech)
            ->get(route('tickets.index', ['status' => 'resolved']))
            ->assertOk()
            ->assertViewHas('statusCounts', self::ALL);
    }

    public function test_technician_can_add_a_faq_entry(): void
    {
        $tech = User::factory()->withRole('technician')->create();

        $this->actingAs($tech)->get(route('homepage-faq.index'))->assertOk();
        $this->actingAs($tech)->get(route('homepage-faq.create'))->assertOk();

        $this->actingAs($tech)->post(route('homepage-faq.store'), [
            'title' => 'Как подключиться к Wi-Fi?',
            'content' => 'Выберите сеть колледжа и введите пароль, который выдаёт техник.',
            'is_active' => 1,
        ])->assertRedirect(route('homepage-faq.index'));

        $this->assertDatabaseHas('homepage_faqs', [
            'title' => 'Как подключиться к Wi-Fi?',
            'author_id' => $tech->id,
        ]);
    }

    public function test_regular_user_cannot_manage_faq(): void
    {
        $user = User::factory()->withRole('user')->create();

        $this->actingAs($user)->get(route('homepage-faq.index'))->assertForbidden();
        $this->actingAs($user)->post(route('homepage-faq.store'), [
            'title' => 'Попытка добавить вопрос',
            'content' => 'Обычный пользователь не должен вести частые вопросы.',
        ])->assertForbidden();
        $this->assertSame(0, HomepageFAQ::count());
    }

    public function test_home_page_offers_to_add_a_faq_entry_only_to_those_who_can(): void
    {
        $tech = User::factory()->withRole('technician')->create();

        $this->actingAs($tech)->get('/')
            ->assertOk()
            ->assertSee(route('homepage-faq.create'), false)
            ->assertSee('Добавить вопрос');

        // Гость кнопки не видит (actingAs держится до конца теста, поэтому выходим).
        \Illuminate\Support\Facades\Auth::logout();
        $this->get('/')->assertDontSee(route('homepage-faq.create'), false);
    }

    public function test_technician_menu_lists_the_sections_open_to_them(): void
    {
        $tech = User::factory()->withRole('technician')->create();

        $html = $this->actingAs($tech)->get('/')->assertOk()->getContent();

        foreach (['Оборудование', 'Закупки', 'Документы', 'FAQ главной', 'Категории знаний'] as $label) {
            $this->assertStringContainsString($label, $html, "В меню техника нет «{$label}»");
        }
        // Эти разделы остаются за администратором и мастером.
        $this->assertStringNotContainsString(route('user.index'), $html);
        $this->assertStringNotContainsString(route('room.index'), $html);
    }
}
