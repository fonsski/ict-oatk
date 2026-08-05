<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\EquipmentStatus;
use App\Models\EquipmentCategory;
use App\Models\OperatingSystem;
use App\Models\Room;
use App\Models\EquipmentLocationHistory;
use App\Traits\HasLiveSearch;
use App\Events\EquipmentStatusChanged;
use App\Events\EquipmentLocationChanged;
use App\Http\Requests\StoreEquipmentRequest;
use App\Http\Requests\UpdateEquipmentRequest;
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
        $query = Equipment::with(["status", "room", "category", "operatingSystem"]);

        // Filters
        if ($request->filled("status_id")) {
            $query->where("status_id", $request->input("status_id"));
        }

        // Filter by category
        if ($request->filled("category_id")) {
            $query->where("category_id", $request->input("category_id"));
        }

        // Filter by operating system ("none" — техника без указанной ОС)
        if ($request->filled("operating_system_id")) {
            $operatingSystemId = $request->input("operating_system_id");
            $operatingSystemId === "none"
                ? $query->whereNull("operating_system_id")
                : $query->where("operating_system_id", $operatingSystemId);
        }

        // Filter by room ("none" — техника без кабинета)
        if ($request->filled("room_id")) {
            $roomId = $request->input("room_id");
            $roomId === "none"
                ? $query->whereNull("room_id")
                : $query->where("room_id", $roomId);
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

        $equipment = $this->applySorting($query, $request)
            ->paginate(15)
            ->withQueryString();

        $statuses = EquipmentStatus::all();
        $categories = EquipmentCategory::orderBy("name")->get();
        $operatingSystems = OperatingSystem::ordered()->get();
        $rooms = Room::orderBy("number")->get();

        return view(
            "equipment.index",
            compact(
                "equipment",
                "statuses",
                "categories",
                "operatingSystems",
                "rooms",
            ),
        );
    }

    /**
     * Доступные варианты сортировки списка оборудования.
     */
    public const SORTS = [
        "latest" => "Сначала новые",
        "room" => "По кабинетам",
        "operating_system" => "По операционной системе",
        "inventory_number" => "По инвентарному номеру",
        "category" => "По категории",
    ];

    /**
     * Сортировка списка. Для «по кабинетам» и «по ОС» задаём порядок и
     * внутри группы — иначе строки внутри кабинета шли бы вперемешку.
     * Кабинет/ОС могут быть не указаны — такие строки уходят в конец.
     */
    protected function applySorting($query, Request $request)
    {
        return match ($request->input("sort")) {
            "room" => $query
                ->leftJoin("rooms", "equipment.room_id", "=", "rooms.id")
                ->orderByRaw("equipment.room_id is null")
                ->orderBy("rooms.building")
                ->orderBy("rooms.floor")
                ->orderBy("rooms.number")
                ->orderBy("equipment.inventory_number")
                ->select("equipment.*"),
            "operating_system" => $query
                ->leftJoin(
                    "operating_systems",
                    "equipment.operating_system_id",
                    "=",
                    "operating_systems.id",
                )
                ->orderByRaw("equipment.operating_system_id is null")
                ->orderBy("operating_systems.family")
                ->orderBy("operating_systems.sort_order")
                ->orderBy("operating_systems.name")
                ->orderBy("equipment.inventory_number")
                ->select("equipment.*"),
            "inventory_number" => $query->orderBy("inventory_number"),
            "category" => $query
                ->leftJoin(
                    "equipment_categories",
                    "equipment.category_id",
                    "=",
                    "equipment_categories.id",
                )
                ->orderByRaw("equipment.category_id is null")
                ->orderBy("equipment_categories.name")
                ->orderBy("equipment.inventory_number")
                ->select("equipment.*"),
            default => $query->latest(),
        };
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
        $operatingSystems = OperatingSystem::active()->ordered()->get();

        return view(
            "equipment.create",
            compact("statuses", "rooms", "categories", "operatingSystems"),
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEquipmentRequest $request)
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

        $data = $this->clearOperatingSystemIfUnsupported($request->validated());

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
            "category",
            "operatingSystem",
            "writeOff",
            "purchase",
            "locationHistory.fromRoom",
            "locationHistory.toRoom",
            "locationHistory.movedByUser",
            "consumableMovements" => function ($query) {
                $query
                    ->with(["consumable", "consumableWriteOff"])
                    ->latest("moved_at")
                    ->latest("id");
            },
            "documents" => function ($query) {
                $query->with("uploadedBy")->latest();
            },
            "knowledgeArticles.category",
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
        $operatingSystems = OperatingSystem::active()->ordered()->get();

        return view(
            "equipment.edit",
            compact(
                "equipment",
                "statuses",
                "rooms",
                "categories",
                "operatingSystems",
            ),
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEquipmentRequest $request, Equipment $equipment)
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

        $data = $this->clearOperatingSystemIfUnsupported(
            $request->validated(),
            $equipment,
        );

        // Проверяем, изменился ли кабинет и статус
        $oldRoomId = $equipment->room_id;
        $newRoomId = $data["room_id"] ?? null;
        $oldStatusId = $equipment->status_id;
        $newStatusId = $data["status_id"] ?? null;

        // Обновляем оборудование
        $equipment->update($data);

        // Если статус изменился, отправляем событие
        if ($oldStatusId !== $newStatusId) {
            $oldStatus = EquipmentStatus::find($oldStatusId);
            $newStatus = EquipmentStatus::find($newStatusId);
            event(new EquipmentStatusChanged(
                $equipment, 
                $oldStatus ? $oldStatus->name : 'Неизвестно',
                $newStatus ? $newStatus->name : 'Неизвестно',
                Auth::user()
            ));
        }

        // Если кабинет изменился, записываем историю перемещения и отправляем событие
        if ($oldRoomId !== $newRoomId) {
            $equipment->recordLocationChange(
                $oldRoomId,
                $newRoomId,
                "Перемещение при обновлении данных оборудования",
            );
            
            event(new EquipmentLocationChanged(
                $equipment,
                $oldRoomId,
                $newRoomId,
                Auth::user()
            ));
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
            "relations" => ["status", "room", "category", "operatingSystem"],
            "per_page" => 15,
        ];
    }

    /**
     * ОС хранится только у техники, чья категория это допускает (ПК,
     * ноутбуки). Если категорию сменили на «Монитор», привязку снимаем,
     * чтобы в базе не оставалось поле, которого нет в форме.
     */
    private function clearOperatingSystemIfUnsupported(
        array $data,
        ?Equipment $equipment = null,
    ): array {
        $categoryId = array_key_exists("category_id", $data)
            ? $data["category_id"]
            : $equipment?->category_id;

        $supportsOs = $categoryId
            ? (bool) EquipmentCategory::whereKey($categoryId)->value(
                "has_operating_system",
            )
            : false;

        if (!$supportsOs) {
            $data["operating_system_id"] = null;
        }

        return $data;
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
     * Лёгкий JSON-поиск оборудования по инвентарному номеру/названию —
     * используется для выбора оборудования при установке расходника.
     */
    public function picker(Request $request)
    {
        $search = trim((string) $request->input("q"));

        $query = Equipment::query()->select(
            "id",
            "inventory_number",
            "name",
        );

        if ($search !== "") {
            $query->where(function ($q) use ($search) {
                $q->where("inventory_number", "like", "%{$search}%")->orWhere(
                    "name",
                    "like",
                    "%{$search}%",
                );
            });
        }

        $equipment = $query->orderBy("inventory_number")->limit(10)->get();

        return response()->json(["data" => $equipment]);
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

            // Добавляем фильтр по ОС
            if ($request->filled("operating_system_id")) {
                $operatingSystemId = $request->input("operating_system_id");
                $operatingSystemId === "none"
                    ? $query->whereNull("operating_system_id")
                    : $query->where("operating_system_id", $operatingSystemId);
            }

            // Добавляем фильтр по кабинету
            if ($request->filled("room_id")) {
                $roomId = $request->input("room_id");
                $roomId === "none"
                    ? $query->whereNull("room_id")
                    : $query->where("room_id", $roomId);
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
