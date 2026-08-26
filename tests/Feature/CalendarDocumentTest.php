<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\CalendarEventParticipant;
use App\Models\CalendarTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Документы событий и задач: загрузка доступна всем, кто видит объект;
 * документ может быть открытым или закрытым.
 */
class CalendarDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        Storage::fake("local");
    }

    private function pdf(string $name = "акт.pdf"): UploadedFile
    {
        return UploadedFile::fake()->create($name, 100, "application/pdf");
    }

    private function attach(User $as, CalendarEvent $event, bool $private = false)
    {
        return $this->actingAs($as)->post(
            route("calendar.documents.store", ["type" => "event", "id" => $event->id]),
            ["file" => $this->pdf(), "is_private" => $private ? "1" : "0"],
        );
    }

    public function test_a_participant_technician_can_attach_a_document(): void
    {
        $organizer = User::factory()->withRole("master")->create();
        $tech = User::factory()->withRole("technician")->create();
        $event = CalendarEvent::factory()->create(["organizer_id" => $organizer->id]);
        CalendarEventParticipant::create(["event_id" => $event->id, "user_id" => $tech->id]);

        $this->attach($tech, $event)->assertRedirect();

        $this->assertDatabaseHas("documents", [
            "documentable_type" => CalendarEvent::class,
            "documentable_id" => $event->id,
        ]);
    }

    public function test_outsider_cannot_attach(): void
    {
        $organizer = User::factory()->withRole("technician")->create();
        $outsider = User::factory()->withRole("technician")->create();
        $event = CalendarEvent::factory()->create(["organizer_id" => $organizer->id]);

        $this->attach($outsider, $event)->assertForbidden();
    }

    public function test_open_document_is_visible_to_other_participants(): void
    {
        $organizer = User::factory()->withRole("master")->create();
        $tech = User::factory()->withRole("technician")->create();
        $event = CalendarEvent::factory()->create(["organizer_id" => $organizer->id]);
        CalendarEventParticipant::create(["event_id" => $event->id, "user_id" => $tech->id]);

        $this->attach($organizer, $event, private: false);
        $document = $event->documents()->first();

        // Участник видит открытый документ.
        $this->actingAs($tech)->get(route("calendar.documents.download", $document))->assertOk();
    }

    public function test_private_document_is_hidden_from_other_participants(): void
    {
        $organizer = User::factory()->withRole("master")->create();
        $tech = User::factory()->withRole("technician")->create();
        $another = User::factory()->withRole("technician")->create();
        $event = CalendarEvent::factory()->create(["organizer_id" => $organizer->id]);
        CalendarEventParticipant::create(["event_id" => $event->id, "user_id" => $tech->id]);
        CalendarEventParticipant::create(["event_id" => $event->id, "user_id" => $another->id]);

        // Техник приложил закрытый документ.
        $this->attach($tech, $event, private: true);
        $document = $event->documents()->first();

        // Автор видит.
        $this->actingAs($tech)->get(route("calendar.documents.download", $document))->assertOk();
        // Другой участник — нет.
        $this->actingAs($another)->get(route("calendar.documents.download", $document))->assertForbidden();
        // Управляющий (организатор-мастер) видит.
        $this->actingAs($organizer)->get(route("calendar.documents.download", $document))->assertOk();
    }

    public function test_only_uploader_or_manager_can_delete(): void
    {
        $organizer = User::factory()->withRole("technician")->create();
        $manager = User::factory()->withRole("master")->create();
        $other = User::factory()->withRole("technician")->create();
        $event = CalendarEvent::factory()->create(["organizer_id" => $organizer->id]);
        CalendarEventParticipant::create(["event_id" => $event->id, "user_id" => $other->id]);

        $this->attach($organizer, $event);
        $document = $event->documents()->first();

        // Другой участник, не автор, удалить не может.
        $this->actingAs($other)->delete(route("calendar.documents.destroy", $document))->assertForbidden();
        // Управляющий может.
        $this->actingAs($manager)->delete(route("calendar.documents.destroy", $document))->assertRedirect();
        $this->assertDatabaseMissing("documents", ["id" => $document->id]);
    }

    public function test_task_owner_can_attach_and_download(): void
    {
        $owner = User::factory()->withRole("technician")->create();
        $task = CalendarTask::factory()->for($owner)->create(["created_by_user_id" => $owner->id]);

        $this->actingAs($owner)->post(
            route("calendar.documents.store", ["type" => "task", "id" => $task->id]),
            ["file" => $this->pdf("инструкция.pdf")],
        );

        $document = $task->documents()->first();
        $this->assertNotNull($document);
        $this->actingAs($owner)->get(route("calendar.documents.download", $document))->assertOk();
    }

    public function test_unrelated_type_is_rejected(): void
    {
        $master = User::factory()->withRole("master")->create();

        $this->actingAs($master)
            ->post(route("calendar.documents.store", ["type" => "equipment", "id" => 1]), [
                "file" => $this->pdf(),
            ])
            ->assertNotFound();
    }
}
