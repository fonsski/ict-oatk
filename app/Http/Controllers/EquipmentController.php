<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\EquipmentStatus;
use App\Models\EquipmentCategory;
use App\Models\Room;
use App\Models\EquipmentLocationHistory;
use App\Traits\HasLiveSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EquipmentController extends Controller
{
    use HasLiveSearch;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Equipment::with(["status", "room"]);

        // Filters
        if ($request->filled("status_id")) {
            $query->where("status_id", $request->input("status_id"));
        }

        // Filter by category
        if ($request->filled("category_id")) {
            $query->where("category_id", $request->input("category_id"));
        }

        // Добавляем фильтр по гарантии
        if ($request->filled("warranty")) {
            $warrantyFilter = $request->input("warranty");

            if ($warrantyFilter === "active") {
                // Активная гарантия
                $query
                    ->where("has_warranty", true)
                    ->whereDate("warranty_end_date", ">=", now());
            } elseif ($warrantyFilter === "expired") {
                // Истекшая гарантия
                $query
                    ->where("has_warranty", true)
                    ->whereDate("warranty_end_date", "<", now());
            } elseif ($warrantyFilter === "none") {
                // Без гарантии
                $query->where("has_warranty", false);
            }
        }

        // Search with dynamic field
        $search = $request->input("search");
        $searchBy = $request->input("search_by", "inventory_number");
        if ($search) {
            if (in_array($searchBy, ["id", "inventory_number"])) {
                if ($searchBy === "id") {
                    $query->where("id", $search);
                } else {
                    $query->where("inventory_number", "like", "%{$search}%");
                }
            } elseif ($searchBy === "status") {
                $query->whereHas("status", function ($q) use ($search) {
                    $q->where("name", "like", "%{$search}%");
                });
            }
        }

        $equipment = $query->latest()->paginate(15)->withQueryString();

        $statuses = EquipmentStatus::all();
        $categories = EquipmentCategory::orderBy("name")->get();
        return view(
            "equipment.index",
            compact("equipment", "statuses", "categories"),
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (!Auth::check() || !Auth::user()->canManageEquipment()) {
            abort(403);
        }

        $statuses = EquipmentStatus::all();
        $rooms = Room::active()->orderBy("number")->get();
        $categories = EquipmentCategory::orderBy("name")->get();

        return view(
            "equipment.create",
            compact("statuses", "rooms", "categories"),
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (
            !Auth::check() ||
            !in_array(optional(Auth::user()->role)->slug, ["admin", "master"])
        ) {
            abort(
                403,
                "Только администраторы и мастера могут создавать новое оборудование",
            );
        }

        $messages = [
            "inventory_number.required" =>
                "Пожалуйста, укажите инвентарный номер",
            "inventory_number.unique" =>
                "Оборудование с таким инвентарным номером уже существует",
            "status_id.required" => "Пожалуйста, укажите статус оборудования",
            "warranty_end_date.required_if" =>
                "Укажите дату окончания гарантии",
            "service_comment.max" =>
                "Комментарии о проведенном обслуживании не должны превышать 500 символов",
            "known_issues.max" =>
                "Известные проблемы не должны превышать 500 символов",
        ];

        $data = $request->validate(
            [
                "name" => "nullable|string|max:255",
                "inventory_number" =>
                    "required|string|max:255|unique:equipment,inventory_number",
                "category_id" => "nullable|exists:equipment_categories,id",
                "status_id" => "required|exists:equipment_statuses,id",
                "room_id" => "nullable|exists:rooms,id",
                "has_warranty" => "boolean",
                "warranty_end_date" =>
                    "nullable|date|required_if:has_warranty,1",
                "last_service_date" => "nullable|date",
                "service_comment" => "nullable|string|max:500",
                "known_issues" => "nullable|string|max:500",
                "initial_room_id" => "nullable|exists:rooms,id",
            ],
            $messages,
        );

        // Всегда используем текущий кабинет как начальный при создании оборудования
        if (!empty($data["room_id"])) {
            $data["initial_room_id"] = $data["room_id"];
        }

        $equipment = Equipment::create($data);

        // Записываем начальное размещение, если указан кабинет
        if (!empty($equipment->room_id)) {
            $equipment->recordInitialLocation(
                $equipment->room_id,
                "Первоначальное размещение при создании оборудования",
            );
        } elseif (!empty($equipment->initial_room_id)) {
            // Если room_id пустой, но initial_room_id указан, используем его
            $equipment->recordInitialLocation(
                $equipment->initial_room_id,
                "Первоначальное размещение при создании оборудования",
            );
        }

        return redirect()
            ->route("equipment.index")
            ->with("success", "Оборудование добавлено");
    }

    /**
     * Display the specified resource.
     */
    public function show(Equipment $equipment)
    {
        $equipment->load([
            "status",
            "room",
            "locationHistory.fromRoom",
            "locationHistory.toRoom",
            "locationHistory.movedByUser",
        ]);
        return view("equipment.show", compact("equipment"));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Equipment $equipment)
    {
        if (!Auth::check() || !Auth::user()->canManageEquipment()) {
            abort(403);
        }

        $statuses = EquipmentStatus::all();
        $rooms = Room::active()->orderBy("number")->get();
        $categories = EquipmentCategory::orderBy("name")->get();

        return view(
            "equipment.edit",
            compact("equipment", "statuses", "rooms", "categories"),
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Equipment $equipment)
    {
        if (
            !Auth::check() ||
            !in_array(optional(Auth::user()->role)->slug, ["admin", "master"])
        ) {
            abort(
                403,
                "Только администраторы и мастера могут изменять оборудование",
            );
        }

        $messages = [
            "inventory_number.required" =>
                "Пожалуйста, укажите инвентарный номер",
            "inventory_number.unique" =>
                "Оборудование с таким инвентарным номером уже существует",
            "status_id.required" => "Пожалуйста, укажите статус оборудования",
            "warranty_end_date.required_if" =>
                "Укажите дату окончания гарантии",
            "service_comment.max" =>
                "Комментарии о проведенном обслуживании не должны превышать 500 символов",
            "known_issues.max" =>
                "Известные проблемы не должны превышать 500 символов",
        ];

        $data = $request->validate(
            [
                "name" => "nullable|string|max:255",
                "inventory_number" =>
                    "required|string|max:255|unique:equipment,inventory_number," .
                    $equipment->id,
                "category_id" => "nullable|exists:equipment_categories,id",
                "status_id" => "required|exists:equipment_statuses,id",
                "room_id" => "nullable|exists:rooms,id",
                "has_warranty" => "boolean",
                "warranty_end_date" =>
                    "nullable|date|required_if:has_warranty,1",
                "last_service_date" => "nullable|date",
                "service_comment" => "nullable|string|max:500",
                "known_issues" => "nullable|string|max:500",
                "initial_room_id" => "nullable|exists:rooms,id",
            ],
            $messages,
        );

        // Проверяем, изменился ли кабинет
        $oldRoomId = $equipment->room_id;
        $newRoomId = $data["room_id"] ?? null;

        // Обновляем оборудование
        $equipment->update($data);

        // Если кабинет изменился, записываем историю перемещения
        if ($oldRoomId !== $newRoomId) {
            $equipment->recordLocationChange(
                $oldRoomId,
                $newRoomId,
                "Перемещение при обновлении данных оборудования",
            );
        }

        return redirect()
            ->route("equipment.index")
            ->with("success", "Оборудование обновлено");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Equipment $equipment)
    {
        if (
            !Auth::check() ||
            !in_array(optional(Auth::user()->role)->slug, ["admin", "master"])
        ) {
            abort(
                403,
                "Только администраторы и мастера могут удалять оборудование",
            );
        }

        $equipment->delete();
        return redirect()
            ->route("equipment.index")
            ->with("success", "Оборудование удалено");
    }

    /**
     * Get search configuration for equipment
     */
    protected function getSearchConfig(): array
    {
        return [
            "fields" => ["inventory_number", "name"],
            "filters" => [
                "status_id" => "status_id",
                "category_id" => "category_id",
            ],
            "relations" => ["status", "room", "category"],
            "per_page" => 15,
        ];
    }

    /**
     * Отображение истории перемещений оборудования
     */
    public function locationHistory(Equipment $equipment)
    {
        $equipment->load([
            "locationHistory.fromRoom",
            "locationHistory.toRoom",
            "locationHistory.movedByUser",
        ]);
        $history = $equipment
            ->locationHistory()
            ->orderBy("move_date", "desc")
            ->get();

        return view(
            "equipment.location_history",
            compact("equipment", "history"),
        );
    }

    /**
     * Форма для перемещения оборудования
     */
    public function moveForm(Equipment $equipment)
    {
        if (!Auth::check() || !Auth::user()->canManageEquipment()) {
            abort(403);
        }

        $rooms = Room::active()->orderBy("number")->get();

        return view("equipment.move", compact("equipment", "rooms"));
    }

    /**
     * Обработка перемещения оборудования
     */
    public function move(Request $request, Equipment $equipment)
    {
        if (
            !Auth::check() ||
            !in_array(optional(Auth::user()->role)->slug, ["admin", "master"])
        ) {
            abort(
                403,
                "Только администраторы и мастера могут перемещать оборудование",
            );
        }

        $data = $request->validate([
            "room_id" => "nullable|exists:rooms,id",
            "comment" => "nullable|string|max:255",
        ]);

        // Перемещаем оборудование
        $equipment->moveToRoom(
            $data["room_id"],
            $data["comment"] ?? "Перемещение оборудования",
        );

        return redirect()
            ->route("equipment.show", $equipment)
            ->with("success", "Оборудование успешно перемещено");
    }

    /**
     * API endpoint for live search
     */
    public function search(Request $request)
    {
        return $this->buildSearchResponse(function () use ($request) {
            $query = Equipment::query();

            // Handle dynamic search field
            $searchBy = $request->input("search_by", "inventory_number");
            $search = $request->input("search");

            if ($search) {
                if (in_array($searchBy, ["id", "inventory_number"])) {
                    if ($searchBy === "id") {
                        $query->where("id", $search);
                    } else {
                        $query->where(
                            "inventory_number",
                            "like",
                            "%{$search}%",
                        );
                    }
                } elseif ($searchBy === "status") {
                    $query->whereHas("status", function ($q) use ($search) {
                        $q->where("name", "like", "%{$search}%");
                    });
                }
            }

            // Добавляем фильтр по статусу
            if ($request->filled("status_id")) {
                $query->where("status_id", $request->input("status_id"));
            }

            // Добавляем фильтр по категории
            if ($request->filled("category_id")) {
                $query->where("category_id", $request->input("category_id"));
            }

            // Добавляем фильтр по гарантии
            if ($request->filled("warranty")) {
                $warrantyFilter = $request->input("warranty");

                if ($warrantyFilter === "active") {
                    // Активная гарантия
                    $query
                        ->where("has_warranty", true)
                        ->whereDate("warranty_end_date", ">=", now());
                } elseif ($warrantyFilter === "expired") {
                    // Истекшая гарантия
                    $query
                        ->where("has_warranty", true)
                        ->whereDate("warranty_end_date", "<", now());
                } elseif ($warrantyFilter === "none") {
                    // Без гарантии
                    $query->where("has_warranty", false);
                }
            }

            return $this->handleLiveSearch(
                $request,
                $query,
                "equipment.partials.table",
            );
        });
    }
}
