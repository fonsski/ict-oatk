<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    

    
     * Список всех кабинетов

    public function index(Request $request)
    {
        $query = Room::with(["equipment"])
            ->orderBy("building", "asc")
            ->orderBy("floor", "asc")
            ->orderBy("number", "asc");

        
        if ($request->filled("status")) {
            $query->withStatus($request->status);
        }

        
        if ($request->filled("type")) {
            $query->ofType($request->type);
        }

        
        if ($request->filled("building")) {
            $query->inBuilding($request->building);
        }

        
        if ($request->filled("floor")) {
            $query->onFloor($request->floor);
        }

        
        if ($request->filled("active")) {
            if ($request->active === "true") {
                $query->active();
            } elseif ($request->active === "false") {
                $query->where("is_active", false);
            }
        }

        
        if ($request->filled("search")) {
            $query->search($request->search);
        }

        $rooms = $query->paginate(15);

        
        $buildings = Room::distinct()->pluck("building")->filter()->sort();
        $floors = Room::distinct()->pluck("floor")->filter()->sort();
        $types = Room::TYPES;
        $statuses = Room::STATUSES;

        return view(
            "room.index",
            compact("rooms", "buildings", "floors", "types", "statuses"),
        );
    }

    
     * Форма создания кабинета

    public function create()
    {
        $types = Room::TYPES;
        $statuses = Room::STATUSES;
        $buildings = Room::distinct()->pluck("building")->filter()->sort();

        return view("room.create", compact("types", "statuses", "buildings"));
    }

    
     * Сохранение нового кабинета

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "number" => "required|string|max:50|unique:rooms",
            "name" => "required|string|max:255",
            "description" => "nullable|string|max:1000",
            "floor" => "nullable|string|max:50",
            "building" => "nullable|string|max:100",
            "capacity" => "nullable|integer|min:1|max:1000",
            "type" =>
                "required|string|in:" . implode(",", array_keys(Room::TYPES)),
            "status" =>
                "required|string|in:" .
                implode(",", array_keys(Room::STATUSES)),
            "responsible_person" => "nullable|string|max:255",
            "phone" => "nullable|string|max:50",
            "notes" => "nullable|string|max:1000",
            "is_active" => "boolean",
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $room = Room::create([
            "number" => $request->number,
            "name" => $request->name,
            "description" => $request->description,
            "floor" => $request->floor,
            "building" => $request->building,
            "capacity" => $request->capacity,
            "type" => $request->type,
            "status" => $request->status,
            "responsible_person" => $request->responsible_person,
            "phone" => $request->phone,
            "notes" => $request->notes,
            "is_active" => $request->boolean("is_active", true),
        ]);

        return redirect()
            ->route("room.index")
            ->with("success", "Кабинет успешно создан");
    }

    
     * Просмотр кабинета

    public function show(Room $room)
    {
        $room->load(["equipment.status", "equipment.category", "tickets", "responsibleUser"]);

        
        $stats = [
            "total_equipment" => $room->equipment->count(),
            "active_equipment" => $room->equipment
                ->filter(function ($equipment) {
                    return $equipment->status &&
                        $equipment->status->slug === "working";
                })
                ->count(),
            "total_tickets" => $room->tickets->count(),
            "recent_tickets" => $room->tickets
                ->filter(function ($ticket) {
                    return $ticket->created_at >= now()->subDays(30);
                })
                ->count(),
        ];

        
        $users = \App\Models\User::active()->orderBy("name")->get();

        return view("room.show", compact("room", "stats", "users"));
    }

    
     * Форма редактирования кабинета

    public function edit(Room $room)
    {
        $types = Room::TYPES;
        $statuses = Room::STATUSES;
        $buildings = Room::distinct()->pluck("building")->filter()->sort();
        $users = \App\Models\User::active()->orderBy("name")->get();

        return view(
            "room.edit",
            compact("room", "types", "statuses", "buildings", "users"),
        );
    }

    
     * Обновление кабинета

    public function update(Request $request, Room $room)
    {
        $validator = Validator::make($request->all(), [
            "number" => [
                "required",
                "string",
                "max:50",
                Rule::unique("rooms")->ignore($room->id),
            ],
            "name" => "required|string|max:255",
            "description" => "nullable|string|max:1000",
            "floor" => "nullable|string|max:50",
            "building" => "nullable|string|max:100",
            "capacity" => "nullable|integer|min:1|max:1000",
            "type" =>
                "required|string|in:" . implode(",", array_keys(Room::TYPES)),
            "status" =>
                "required|string|in:" .
                implode(",", array_keys(Room::STATUSES)),
            "responsible_person" => "nullable|string|max:255",
            "responsible_user_id" => "nullable|exists:users,id",
            "phone" => "nullable|string|max:50",
            "notes" => "nullable|string|max:1000",
            "is_active" => "boolean",
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $room->update([
            "number" => $request->number,
            "name" => $request->name,
            "description" => $request->description,
            "floor" => $request->floor,
            "building" => $request->building,
            "capacity" => $request->capacity,
            "type" => $request->type,
            "status" => $request->status,
            "responsible_person" => $request->responsible_person,
            "responsible_user_id" => $request->responsible_user_id,
            "phone" => $request->phone,
            "notes" => $request->notes,
            "is_active" => $request->boolean("is_active"),
        ]);

        return redirect()
            ->route("room.index")
            ->with("success", "Кабинет успешно обновлен");
    }

    
     * Удаление кабинета

    public function destroy(Room $room)
    {
        
        if (
            $room
                ->equipment()
                ->whereRelation("status", "slug", "working")
                ->exists()
        ) {
            return redirect()
                ->route("room.index")
                ->with(
                    "error",
                    "Нельзя удалить кабинет с активным оборудованием",
                );
        }

        
        if (
            $room
                ->tickets()
                ->whereIn("status", ["open", "in_progress"])
                ->exists()
        ) {
            return redirect()
                ->route("room.index")
                ->with("error", "Нельзя удалить кабинет с активными заявками");
        }

        $room->delete();

        return redirect()
            ->route("room.index")
            ->with("success", "Кабинет успешно удален");
    }

    
     * Изменение статуса кабинета

    public function changeStatus(Request $request, Room $room)
    {
        $validator = Validator::make($request->all(), [
            "status" =>
                "required|string|in:" .
                implode(",", array_keys(Room::STATUSES)),
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        $room->changeStatus($request->status);

        return redirect()
            ->back()
            ->with(
                "success",
                "Статус кабинета изменен на: {$room->status_name}",
            );
    }

    
     * Изменение активности кабинета

    public function toggleActive(Room $room)
    {
        if ($room->is_active) {
            $room->deactivate();
            $message = "Кабинет деактивирован";
        } else {
            $room->activate();
            $message = "Кабинет активирован";
        }

        return redirect()->back()->with("success", $message);
    }

    
     * Массовые операции с кабинетами

    public function bulkAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "action" => "required|in:activate,deactivate,change_status,delete",
            "room_ids" => "required|array",
            "room_ids.*" => "exists:rooms,id",
            "new_status" =>
                "required_if:action,change_status|string|in:" .
                implode(",", array_keys(Room::STATUSES)),
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        $roomIds = $request->room_ids;
        $rooms = Room::whereIn("id", $roomIds);

        switch ($request->action) {
            case "activate":
                $rooms->update(["is_active" => true]);
                $message = "Кабинеты успешно активированы";
                break;

            case "deactivate":
                $rooms->update(["is_active" => false]);
                $message = "Кабинеты успешно деактивированы";
                break;

            case "change_status":
                $rooms->update(["status" => $request->new_status]);
                $message = "Статус кабинетов успешно изменен";
                break;

            case "delete":
                
                $roomsWithEquipment = Room::whereIn("id", $roomIds)
                    ->whereHas("equipment", function ($query) {
                        $query->whereRelation("status", "slug", "working");
                    })
                    ->pluck("number")
                    ->toArray();

                $roomsWithTickets = Room::whereIn("id", $roomIds)
                    ->whereHas("tickets", function ($query) {
                        $query->whereIn("status", ["open", "in_progress"]);
                    })
                    ->pluck("number")
                    ->toArray();

                if (!empty($roomsWithEquipment) || !empty($roomsWithTickets)) {
                    $errors = [];
                    if (!empty($roomsWithEquipment)) {
                        $errors[] =
                            "Следующие кабинеты имеют активное оборудование: " .
                            implode(", ", $roomsWithEquipment);
                    }
                    if (!empty($roomsWithTickets)) {
                        $errors[] =
                            "Следующие кабинеты имеют активные заявки: " .
                            implode(", ", $roomsWithTickets);
                    }

                    return redirect()
                        ->back()
                        ->withErrors(["bulk" => implode("; ", $errors)]);
                }

                $rooms->delete();
                $message = "Кабинеты успешно удалены";
                break;
        }

        return redirect()->route("room.index")->with("success", $message);
    }

    
     * Экспорт кабинетов в CSV

    public function export(Request $request)
    {
        $rooms = Room::with("equipment")->get();

        $filename = "rooms_" . date("Y-m-d_H-i-s") . ".csv";

        $headers = [
            "Content-Type" => "text/csv",
            "Content-Disposition" => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($rooms) {
            $file = fopen("php:

            
            fputcsv($file, [
                "ID",
                "Номер",
                "Название",
                "Тип",
                "Статус",
                "Здание",
                "Этаж",
                "Вместимость",
                "Ответственный",
                "Телефон",
                "Оборудование",
                "Дата создания",
            ]);

            
            foreach ($rooms as $room) {
                fputcsv($file, [
                    $room->id,
                    $room->number,
                    $room->name,
                    $room->type_name,
                    $room->status_name,
                    $room->building,
                    $room->floor,
                    $room->capacity,
                    $room->responsible_person,
                    $room->phone,
                    $room->equipment->count(),
                    $room->created_at->format("d.m.Y H:i"),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    
     * Статистика кабинетов

    public function statistics()
    {
        $stats = [
            "total_rooms" => Room::count(),
            "active_rooms" => Room::active()->count(),
            "inactive_rooms" => Room::where("is_active", false)->count(),
            "available_rooms" => Room::available()->count(),
            "rooms_by_type" => Room::selectRaw("type, count(*) as count")
                ->groupBy("type")
                ->pluck("count", "type"),
            "rooms_by_status" => Room::selectRaw("status, count(*) as count")
                ->groupBy("status")
                ->pluck("count", "status"),
            "rooms_by_building" => Room::selectRaw(
                "building, count(*) as count",
            )
                ->whereNotNull("building")
                ->groupBy("building")
                ->pluck("count", "building"),
            "total_capacity" => Room::sum("capacity"),
            "rooms_with_equipment" => Room::whereHas("equipment")->count(),
        ];

        return view("room.statistics", compact("stats"));
    }

    
     * Получение списка кабинетов для AJAX

    public function getRooms(Request $request)
    {
        $query = Room::active()->available();

        if ($request->filled("type")) {
            $query->ofType($request->type);
        }

        if ($request->filled("building")) {
            $query->inBuilding($request->building);
        }

        $rooms = $query
            ->select("id", "number", "name", "type", "capacity")
            ->orderBy("number")
            ->get();

        return response()->json($rooms);
    }
}
