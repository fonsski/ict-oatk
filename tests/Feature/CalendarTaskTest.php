<?php

namespace Tests\Feature;

use App\Models\CalendarTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Личные задачи в календаре: создание, отметка выполнения, границы доступа.
 */
class CalendarTaskTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    public function test_a_task_is_created_for_the_current_user(): void
    {
        $user = User::factory()->withRole("technician")->create();

        $this->actingAs($user)->post(route("calendar.tasks.store"), [
            "title" => "Купить скетчбук A5",
            "due_date" => "2026-08-28",
            "due_all_day" => "1",
            "priority" => "high",
        ])->assertRedirect();

        $this->assertDatabaseHas("calendar_tasks", [
            "title" => "Купить скетчбук A5",
            "user_id" => $user->id,
            "priority" => "high",
            "due_all_day" => true,
        ]);

        $task = CalendarTask::first();
        $this->assertSame("2026-08-28 00:00", $task->due_at->format("Y-m-d H:i"));
    }

    public function test_task_with_time_keeps_it(): void
    {
        $user = User::factory()->withRole("technician")->create();

        $this->actingAs($user)->post(route("calendar.tasks.store"), [
            "title" => "Позвонить подрядчику",
            "due_date" => "2026-08-28",
            "due_time" => "15:30",
        ]);

        $this->assertSame("15:30", CalendarTask::first()->due_at->format("H:i"));
    }

    public function test_date_is_required(): void
    {
        $user = User::factory()->withRole("technician")->create();

        $this->actingAs($user)
            ->post(route("calendar.tasks.store"), ["title" => "Без даты"])
            ->assertSessionHasErrors("due_date");
    }

    public function test_toggling_marks_complete_and_back(): void
    {
        $user = User::factory()->withRole("technician")->create();
        $task = CalendarTask::factory()->for($user)->create(["completed_at" => null]);

        $this->actingAs($user)->post(route("calendar.tasks.toggle", $task));
        $this->assertNotNull($task->refresh()->completed_at);

        $this->actingAs($user)->post(route("calendar.tasks.toggle", $task));
        $this->assertNull($task->refresh()->completed_at);
    }

    public function test_only_the_owner_can_toggle_or_edit(): void
    {
        $owner = User::factory()->withRole("technician")->create();
        $other = User::factory()->withRole("technician")->create();
        $task = CalendarTask::factory()->for($owner)->create();

        $this->actingAs($other)->post(route("calendar.tasks.toggle", $task))->assertForbidden();
        $this->actingAs($other)->get(route("calendar.tasks.edit", $task))->assertForbidden();
        $this->actingAs($other)->delete(route("calendar.tasks.destroy", $task))->assertForbidden();
    }

    public function test_tasks_appear_only_for_their_owner_in_the_month(): void
    {
        $viewer = User::factory()->withRole("technician")->create();
        $other = User::factory()->withRole("technician")->create();

        $mine = CalendarTask::factory()->for($viewer)->create([
            "title" => "Моя задача про кабель",
            "due_at" => "2026-08-15 00:00",
        ]);
        $foreign = CalendarTask::factory()->for($other)->create([
            "title" => "Чужая задача про сервер",
            "due_at" => "2026-08-15 00:00",
        ]);

        $response = $this->actingAs($viewer)->get(route("calendar.index", ["month" => "2026-08"]));

        $response->assertOk();
        $response->assertSee($mine->title);
        $response->assertDontSee($foreign->title);
    }

    public function test_author_still_sees_a_task_handed_to_another(): void
    {
        $author = User::factory()->withRole("technician")->create();
        $assignee = User::factory()->withRole("technician")->create();
        $task = CalendarTask::factory()->for($assignee)->create([
            "title" => "Переданная задача",
            "created_by_user_id" => $author->id,
            "due_at" => "2026-08-15 00:00",
        ]);

        // Автор видит её в календаре, хотя исполнитель — другой.
        $this->actingAs($author)
            ->get(route("calendar.index", ["month" => "2026-08"]))
            ->assertSee("Переданная задача");
    }

    public function test_manager_sees_everyones_tasks(): void
    {
        $master = User::factory()->withRole("master")->create();
        $someone = User::factory()->withRole("technician")->create();
        CalendarTask::factory()->for($someone)->create([
            "title" => "Чужая задача техника",
            "created_by_user_id" => $someone->id,
            "due_at" => "2026-08-15 00:00",
        ]);

        $this->actingAs($master)
            ->get(route("calendar.index", ["month" => "2026-08"]))
            ->assertSee("Чужая задача техника");
    }

    public function test_technician_does_not_see_unrelated_tasks(): void
    {
        $viewer = User::factory()->withRole("technician")->create();
        $other = User::factory()->withRole("technician")->create();
        CalendarTask::factory()->for($other)->create([
            "title" => "Совсем чужая",
            "created_by_user_id" => $other->id,
            "due_at" => "2026-08-15 00:00",
        ]);

        $this->actingAs($viewer)
            ->get(route("calendar.index", ["month" => "2026-08"]))
            ->assertDontSee("Совсем чужая");
    }

    public function test_creator_is_recorded_and_task_can_be_assigned_to_another(): void
    {
        $author = User::factory()->withRole("master")->create();
        $assignee = User::factory()->withRole("technician")->create();

        $this->actingAs($author)->post(route("calendar.tasks.store"), [
            "title" => "Поручение технику",
            "due_date" => "2026-08-28",
            "due_all_day" => "1",
            "user_id" => $assignee->id,
        ]);

        $this->assertDatabaseHas("calendar_tasks", [
            "title" => "Поручение технику",
            "created_by_user_id" => $author->id,
            "user_id" => $assignee->id,
        ]);
    }

    public function test_author_keeps_access_even_if_assigned_to_another(): void
    {
        $author = User::factory()->withRole("master")->create();
        $assignee = User::factory()->withRole("technician")->create();
        $task = CalendarTask::factory()->for($assignee)->create([
            "created_by_user_id" => $author->id,
        ]);

        // Автор может открыть и переключить, хотя исполнитель — другой.
        $this->actingAs($author)->get(route("calendar.tasks.edit", $task))->assertOk();
        $this->actingAs($author)->post(route("calendar.tasks.toggle", $task))->assertRedirect();
    }

    public function test_owner_can_update_a_task(): void
    {
        $user = User::factory()->withRole("technician")->create();
        $task = CalendarTask::factory()->for($user)->create();

        $this->actingAs($user)->put(route("calendar.tasks.update", $task), [
            "title" => "Обновлённое название",
            "due_date" => "2026-09-01",
            "due_all_day" => "1",
            "priority" => "low",
        ])->assertRedirect();

        $this->assertDatabaseHas("calendar_tasks", [
            "id" => $task->id,
            "title" => "Обновлённое название",
            "priority" => "low",
        ]);
    }
}
