<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Ссылки в уведомлениях должны быть относительными.
 *
 * Уведомление часто создаётся в обработчике очереди, где хост берётся из
 * APP_URL. Если систему открывают по другому имени или IP (например,
 * helpdesk.oatk.local вместо адреса из APP_URL), полный URL уводит на
 * чужой адрес и переход по уведомлению заканчивается ошибкой.
 */
class NotificationLinkTest extends TestCase
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
            "title" => "Не включается монитор",
            "description" => "Монитор не подаёт признаков жизни.",
            "category" => "hardware",
            "priority" => "medium",
            "status" => "open",
            "reporter_name" => "Иванов Иван",
            "reporter_phone" => "+79001234567",
        ]);
    }

    public function test_ticket_notification_link_is_relative(): void
    {
        Notification::fake();

        // Хост «сервера» намеренно отличается от того, по которому потом
        // откроют систему.
        URL::forceRootUrl("http://ict.college.local");

        $technician = User::factory()->withRole("technician")->create();
        $ticket = $this->ticket();

        app(NotificationService::class)->notifyTicketAssigned(
            $ticket,
            $technician,
        );

        $links = [];
        Notification::assertSentTo(
            $technician,
            \App\Notifications\TicketNotification::class,
            function ($notification) use (&$links) {
                // Полезная нагрузка лежит в защищённом свойстве,
                // читаем её тем же способом, что и Laravel при отправке.
                $payload = $notification->toArray(null);
                $links[] = $payload["link"] ?? null;
                return true;
            },
        );

        $this->assertNotEmpty(array_filter($links));

        foreach (array_filter($links) as $link) {
            $this->assertStringStartsWith("/tickets/", $link);
            $this->assertStringNotContainsString("ict.college.local", $link);
        }
    }

    /**
     * Уведомления, разосланные до исправления, тоже должны открываться:
     * миграция обрезает у них схему и хост.
     */
    public function test_migration_rewrites_already_stored_absolute_links(): void
    {
        $user = User::factory()->withRole("technician")->create();

        DB::table("notifications")->insert([
            "id" => (string) \Illuminate\Support\Str::uuid(),
            "type" => \App\Notifications\TicketNotification::class,
            "notifiable_type" => User::class,
            "notifiable_id" => $user->id,
            "data" => json_encode([
                "title" => "Старое уведомление",
                "link" => "http://ict.college.local/tickets/7?from=mail",
            ]),
            "created_at" => now(),
            "updated_at" => now(),
        ]);

        $this->runNotificationLinkMigration();

        $data = json_decode(
            DB::table("notifications")->value("data"),
            true,
        );

        $this->assertSame("/tickets/7?from=mail", $data["link"]);
    }

    public function test_migration_leaves_already_relative_links_alone(): void
    {
        $user = User::factory()->withRole("technician")->create();

        DB::table("notifications")->insert([
            "id" => (string) \Illuminate\Support\Str::uuid(),
            "type" => \App\Notifications\TicketNotification::class,
            "notifiable_type" => User::class,
            "notifiable_id" => $user->id,
            "data" => json_encode([
                "title" => "Уже относительная",
                "link" => "/tickets/9",
            ]),
            "created_at" => now(),
            "updated_at" => now(),
        ]);

        $this->runNotificationLinkMigration();

        $data = json_decode(DB::table("notifications")->value("data"), true);

        $this->assertSame("/tickets/9", $data["link"]);
    }

    private function runNotificationLinkMigration(): void
    {
        $migration = require database_path(
            "migrations/2026_08_06_163441_make_notification_links_relative.php",
        );

        $migration->up();
    }
}
