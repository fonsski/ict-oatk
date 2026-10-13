<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\EquipmentCategory;
use App\Models\EquipmentStatus;
use App\Models\OperatingSystem;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class EquipmentImportTest extends TestCase
{
    use RefreshDatabase;

    private const HEADER = "inventory_number;name;category;status;room_number;operating_system;has_warranty;warranty_end_date;last_service_date;accounting_number";

    private EquipmentStatus $working;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        Storage::fake("local");

        $this->working = EquipmentStatus::firstOrCreate(["slug" => "working"], ["name" => "Исправно"]);
        EquipmentStatus::firstOrCreate(["slug" => "broken"], ["name" => "Неисправно"]);
    }

    private function master(): User
    {
        return User::factory()->withRole("master")->create();
    }

    private function category(string $name = "Принтер", bool $os = false): EquipmentCategory
    {
        return EquipmentCategory::create([
            "name" => $name,
            "slug" => \Illuminate\Support\Str::slug($name) ?: "cat-" . EquipmentCategory::count(),
            "has_operating_system" => $os,
        ]);
    }

    private function room(string $number = "101"): Room
    {
        return Room::create([
            "number" => $number,
            "name" => "Кабинет {$number}",
            "type" => "other",
            "status" => "available",
            "is_active" => true,
        ]);
    }

    private function preview(string $content, array $options = [], string $fileName = "equipment.csv"): TestResponse
    {
        return $this->actingAs($this->master())->post(route("equipment.import.preview"), array_merge([
            "file" => UploadedFile::fake()->createWithContent($fileName, $content),
            "existing" => "skip",
            "create_missing" => "0",
        ], $options));
    }

    private function confirm(): TestResponse
    {
        return $this->post(route("equipment.import.store"), [
            "token" => session("equipment_import.token"),
        ]);
    }

    private function csv(string ...$rows): string
    {
        return implode("\n", [self::HEADER, ...$rows]);
    }

    public function test_preview_reports_what_will_happen_without_writing_anything(): void
    {
        $this->category();

        $response = $this->preview($this->csv("2101344347;Принтер Canon;Принтер;working;;;;;;"));

        $response->assertOk();
        $response->assertViewIs("equipment.import-preview");
        $this->assertSame(1, $response->viewData("plan")->count("create"));
        $this->assertDatabaseCount("equipment", 0);
    }

    public function test_confirmed_import_creates_equipment_and_its_first_location(): void
    {
        $category = $this->category();
        $room = $this->room("101");

        $this->preview($this->csv("2101344347;Принтер Canon;Принтер;working;101;;;;;"));
        $this->confirm()->assertRedirect(route("equipment.index"));

        $equipment = Equipment::where("inventory_number", "2101344347")->firstOrFail();

        $this->assertSame("Принтер Canon", $equipment->name);
        $this->assertSame($category->id, $equipment->category_id);
        $this->assertSame($room->id, $equipment->room_id);
        $this->assertSame($room->id, $equipment->initial_room_id);
        $this->assertSame($this->working->id, $equipment->status_id);
        $this->assertCount(1, $equipment->locationHistory);
        $this->assertSame([], Storage::disk("local")->allFiles("equipment-imports"));
    }

    public function test_status_defaults_to_working_and_accepts_the_russian_name(): void
    {
        $this->preview($this->csv("1001;Первый;;;;;;;;", "1002;Второй;;Неисправно;;;;;;"));
        $this->confirm();

        $this->assertSame($this->working->id, Equipment::where("inventory_number", "1001")->value("status_id"));
        $this->assertSame(
            EquipmentStatus::where("slug", "broken")->value("id"),
            Equipment::where("inventory_number", "1002")->value("status_id"),
        );
    }

    public function test_any_error_blocks_the_whole_import(): void
    {
        $this->preview($this->csv("1001;Хороший;;working;;;;;;", "abc;Плохой;;working;;;;;;"));

        $this->confirm();

        $this->assertDatabaseCount("equipment", 0);
    }

    public function test_errors_are_listed_with_the_file_line(): void
    {
        $response = $this->preview($this->csv("1001;Хороший;;working;;;;;;", "abc;Плохой;;working;;;;;;"));

        $plan = $response->viewData("plan");

        $this->assertTrue($plan->hasErrors());
        $this->assertSame(3, $plan->errorRows()[0]["line"]);
        $response->assertSee("Строка 3");
    }

    public function test_unknown_category_and_room_are_errors_by_default(): void
    {
        $plan = $this->preview($this->csv("1001;Принтер;Несуществующая;working;999;;;;;"))->viewData("plan");

        $this->assertCount(2, $plan->errorRows()[0]["errors"]);
    }

    public function test_missing_category_and_room_are_created_on_request(): void
    {
        $this->preview(
            $this->csv("1001;Принтер;Новая категория;working;4-ка;;;;;", "1002;Сканер;новая категория;working;4-КА;;;;;"),
            ["create_missing" => "1"],
        );
        $this->confirm();

        $this->assertSame(1, EquipmentCategory::where("name", "Новая категория")->count());
        $this->assertSame(1, Room::where("number", "4-ка")->count());
        $this->assertSame(
            Equipment::where("inventory_number", "1001")->value("room_id"),
            Equipment::where("inventory_number", "1002")->value("room_id"),
        );
    }

    public function test_existing_equipment_is_left_alone_by_default(): void
    {
        Equipment::create(["inventory_number" => "1001", "name" => "Старое имя", "status_id" => $this->working->id]);

        $plan = $this->preview($this->csv("1001;Новое имя;;;;;;;;"))->viewData("plan");
        $this->confirm();

        $this->assertSame(1, $plan->count("skip"));
        $this->assertSame("Старое имя", Equipment::where("inventory_number", "1001")->value("name"));
    }

    public function test_update_mode_changes_given_fields_and_keeps_empty_cells(): void
    {
        $old = $this->room("101");
        $new = $this->room("102");
        $category = $this->category();

        $equipment = Equipment::create([
            "inventory_number" => "1001",
            "name" => "Старое имя",
            "model" => "Модель X",
            "status_id" => $this->working->id,
            "category_id" => $category->id,
            "room_id" => $old->id,
        ]);

        $this->preview($this->csv("1001;Новое имя;;;102;;;;;"), ["existing" => "update"]);
        $this->confirm();

        $equipment->refresh();

        $this->assertSame("Новое имя", $equipment->name);
        $this->assertSame("Модель X", $equipment->model);
        $this->assertSame($category->id, $equipment->category_id);
        $this->assertSame($new->id, $equipment->room_id);
        $this->assertSame($old->id, $equipment->locationHistory()->first()->from_room_id);
    }

    public function test_duplicate_inventory_number_in_one_file_is_an_error(): void
    {
        $plan = $this->preview($this->csv("1001;Один;;;;;;;;", "1001;Два;;;;;;;;"))->viewData("plan");

        $this->assertStringContainsString("уже встречается в строке 2", $plan->errorRows()[0]["errors"][0]);
    }

    public function test_deleted_equipment_cannot_be_updated_through_import(): void
    {
        Equipment::create(["inventory_number" => "1001", "status_id" => $this->working->id])->delete();

        $plan = $this->preview($this->csv("1001;Имя;;;;;;;;"), ["existing" => "update"])->viewData("plan");

        $this->assertTrue($plan->hasErrors());
    }

    public function test_dates_are_accepted_in_russian_and_iso_form_and_bad_ones_are_rejected(): void
    {
        $this->preview($this->csv("1001;Имя;;;;;да;31.12.2099;2025-09-01;"));
        $this->confirm();

        $equipment = Equipment::where("inventory_number", "1001")->firstOrFail();

        $this->assertTrue($equipment->has_warranty);
        $this->assertSame("2099-12-31", $equipment->warranty_end_date->toDateString());
        $this->assertSame("2025-09-01", $equipment->last_service_date->toDateString());

        $plan = $this->preview($this->csv("1002;Имя;;;;;;31.02.2099;;"))->viewData("plan");
        $this->assertTrue($plan->hasErrors());
    }

    public function test_warranty_without_an_end_date_is_an_error(): void
    {
        $plan = $this->preview($this->csv("1001;Имя;;;;;да;;;"))->viewData("plan");

        $this->assertTrue($plan->hasErrors());
    }

    public function test_accounting_number_must_match_the_format_and_be_free(): void
    {
        Equipment::create([
            "inventory_number" => "1",
            "accounting_number" => "А1-сотрудник-001",
            "status_id" => $this->working->id,
        ]);

        $bad = $this->preview($this->csv("1001;Имя;;;;;;;;неверно"))->viewData("plan");
        $taken = $this->preview($this->csv("1002;Имя;;;;;;;;А1-сотрудник-001"))->viewData("plan");
        $free = $this->preview($this->csv("1003;Имя;;;;;;;;А1-сотрудник-002"))->viewData("plan");

        $this->assertTrue($bad->hasErrors());
        $this->assertTrue($taken->hasErrors());
        $this->assertFalse($free->hasErrors());
    }

    public function test_operating_system_is_kept_only_for_categories_that_support_it(): void
    {
        $this->category("Компьютер", os: true);
        $this->category("Монитор", os: false);
        $os = OperatingSystem::create(["name" => "Windows 11 Pro", "slug" => "windows-11-pro", "family" => "Windows", "is_active" => true]);

        $response = $this->preview($this->csv(
            "1001;ПК;Компьютер;;;Windows 11 Pro;;;;",
            "1002;Экран;Монитор;;;Windows 11 Pro;;;;",
        ));
        $this->confirm();

        $this->assertSame($os->id, Equipment::where("inventory_number", "1001")->value("operating_system_id"));
        $this->assertNull(Equipment::where("inventory_number", "1002")->value("operating_system_id"));
        $this->assertCount(1, $response->viewData("plan")->warningRows());
    }

    public function test_json_file_is_imported_as_an_array_or_under_the_equipment_key(): void
    {
        $this->category();

        $this->preview(
            json_encode([["inventory_number" => 2101344347, "name" => "Принтер", "category" => "Принтер"]]),
            [],
            "equipment.json",
        );
        $this->confirm();

        $this->preview(
            json_encode(["equipment" => [["inventory_number" => "2101344348", "has_warranty" => false]]]),
            [],
            "wrapped.json",
        );
        $this->confirm();

        $this->assertDatabaseHas("equipment", ["inventory_number" => "2101344347", "name" => "Принтер"]);
        $this->assertDatabaseHas("equipment", ["inventory_number" => "2101344348", "has_warranty" => false]);
    }

    public function test_broken_json_is_reported_on_the_upload_form(): void
    {
        $this->preview("{не json", [], "equipment.json")->assertSessionHasErrors("file");
    }

    public function test_file_without_the_key_column_is_rejected(): void
    {
        $this->preview("name;category\nПринтер;Принтер")->assertSessionHasErrors("file");
    }

    public function test_unknown_columns_are_reported_but_do_not_block(): void
    {
        $plan = $this->preview("inventory_number;цвет\n1001;красный")->viewData("plan");

        $this->assertSame(["цвет"], $plan->unknownColumns);
        $this->assertFalse($plan->hasErrors());
    }

    public function test_confirming_without_a_checked_file_is_refused(): void
    {
        $this->actingAs($this->master())
            ->post(route("equipment.import.store"), ["token" => "forged"])
            ->assertRedirect(route("equipment.import.create"));

        $this->assertDatabaseCount("equipment", 0);
    }

    public function test_only_admin_and_master_can_import(): void
    {
        $technician = User::factory()->withRole("technician")->create();

        $this->actingAs($technician)->get(route("equipment.import.create"))->assertForbidden();
        $this->actingAs($technician)->post(route("equipment.import.preview"))->assertForbidden();
        $this->actingAs($this->master())->get(route("equipment.import.create"))->assertOk();
    }

    public function test_the_template_downloads_and_is_itself_importable(): void
    {
        $response = $this->actingAs($this->master())->get(route("equipment.import.template"));
        $response->assertOk();

        $this->category("Принтер");
        $this->category("Компьютер", os: true);
        OperatingSystem::create(["name" => "Windows 11 Pro", "slug" => "windows-11-pro", "family" => "Windows", "is_active" => true]);

        $content = $response->streamedContent();
        $plan = $this->preview($content, ["create_missing" => "1"])->viewData("plan");

        $this->assertFalse($plan->hasErrors(), json_encode($plan->errorRows(), JSON_UNESCAPED_UNICODE));
        $this->assertSame(2, $plan->count("create"));
    }
}
