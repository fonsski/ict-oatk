<?php

namespace Tests\Feature;

use App\Models\CalendarTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Отдельная страница со списком задач.
 */
class CalendarTasksPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    public function test_page_lists_active_and_completed_tasks(): void
    {
        $user = User::factory()->withRole("technician")->create();
        CalendarTask::factory()->for($user)->create([
            "title" => "Активная задача",
            "created_by_user_id" => $user->id,
            "completed_at" => null,
        ]);
        CalendarTask::factory()->for($user)->create([
            "title" => "Готовая задача",
            "created_by_user_id" => $user->id,
            "completed_at" => now(),
        ]);

        $response = $this->actingAs($user)->get(route("calendar.tasks.index"));

        $response->assertOk();
        $response->assertSee("Активная задача");
        $response->assertSee("Готовая задача");
        $response->assertSee("Выполнено (1)");
    }

    public function test_page_is_scoped_to_the_viewer(): void
    {
        $viewer = User::factory()->withRole("technician")->create();
        $other = User::factory()->withRole("technician")->create();
        CalendarTask::factory()->for($other)->create([
            "title" => "Чужая недоступная",
            "created_by_user_id" => $other->id,
        ]);

        $this->actingAs($viewer)
            ->get(route("calendar.tasks.index"))
            ->assertOk()
            ->assertDontSee("Чужая недоступная");
    }

    public function test_manager_sees_all_tasks_on_the_page(): void
    {
        $master = User::factory()->withRole("master")->create();
        $tech = User::factory()->withRole("technician")->create();
        CalendarTask::factory()->for($tech)->create([
            "title" => "Задача техника",
            "created_by_user_id" => $tech->id,
        ]);

        $this->actingAs($master)
            ->get(route("calendar.tasks.index"))
            ->assertOk()
            ->assertSee("Задача техника");
    }
}
