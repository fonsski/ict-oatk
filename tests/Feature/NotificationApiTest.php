<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Покрывает API уведомлений и сохранение смыслового типа:
 * колонка type в таблице notifications хранит класс уведомления,
 * поэтому тип вроде new_ticket должен лежать в полезной нагрузке.
 */
class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
    }

    private function notify(User $user, string $type, string $title): void
    {
        app(NotificationService::class)->createNotification([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => 'Текст уведомления',
        ]);
    }

    public function test_guest_cannot_read_notifications(): void
    {
        $this->getJson('/api/notifications')->assertUnauthorized();
    }

    public function test_user_receives_own_notifications(): void
    {
        $user = User::factory()->withRole('user')->create();
        $this->notify($user, 'new_ticket', 'Новая заявка');

        $this->actingAs($user)
            ->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.0.data.title', 'Новая заявка');
    }

    public function test_notifications_are_not_leaked_between_users(): void
    {
        $owner = User::factory()->withRole('user')->create();
        $other = User::factory()->withRole('user')->create();

        $this->notify($owner, 'new_ticket', 'Личное уведомление');

        $this->actingAs($other)
            ->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('unread_count', 0)
            ->assertJsonCount(0, 'notifications');
    }

    public function test_semantic_type_is_persisted_in_payload(): void
    {
        $user = User::factory()->withRole('user')->create();
        $this->notify($user, 'ticket_assigned', 'Назначена заявка');

        $this->assertSame(
            'ticket_assigned',
            $user->notifications()->first()->data['type'],
        );
    }

    public function test_stats_group_by_semantic_type(): void
    {
        $user = User::factory()->withRole('user')->create();

        $this->notify($user, 'new_ticket', 'Заявка 1');
        $this->notify($user, 'new_ticket', 'Заявка 2');
        $this->notify($user, 'ticket_assigned', 'Назначение');

        $this->actingAs($user)
            ->getJson('/api/notifications/stats')
            ->assertOk()
            ->assertJsonPath('stats.total', 3)
            ->assertJsonPath('stats.unread', 3)
            ->assertJsonPath('stats.by_type.new_ticket', 2)
            ->assertJsonPath('stats.by_type.ticket_assigned', 1);
    }

    public function test_unread_count_endpoint(): void
    {
        $user = User::factory()->withRole('user')->create();
        $this->notify($user, 'new_ticket', 'Новая заявка');

        $this->actingAs($user)
            ->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJson(['unread_count' => 1, 'has_unread' => true]);
    }

    public function test_marking_all_as_read_clears_unread_count(): void
    {
        $user = User::factory()->withRole('user')->create();
        $this->notify($user, 'new_ticket', 'Заявка 1');
        $this->notify($user, 'new_ticket', 'Заявка 2');

        $this->actingAs($user)
            ->postJson('/api/notifications/mark-all-as-read')
            ->assertOk()
            ->assertJson(['unread_count' => 0]);

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    public function test_marking_single_notification_as_read(): void
    {
        $user = User::factory()->withRole('user')->create();
        $this->notify($user, 'new_ticket', 'Заявка 1');
        $this->notify($user, 'new_ticket', 'Заявка 2');

        $id = $user->notifications()->first()->id;

        $this->actingAs($user)
            ->postJson("/api/notifications/mark-as-read/{$id}")
            ->assertOk()
            ->assertJson(['unread_count' => 1]);
    }
}
