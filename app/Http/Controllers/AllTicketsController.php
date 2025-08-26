<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\Location;
use App\Models\User;

class AllTicketsController extends Controller
{
    /**
     * Отображение всех заявок в системе
     */
    public function index(Request $request)
    {
        // Проверка аутентификации и роли
        if (
            !Auth::check() ||
            !in_array(optional(Auth::user()->role)->slug, [
                "admin",
                "master",
                "technician",
            ])
        ) {
            abort(403);
        }
        // Базовый запрос с подгрузкой связанных моделей
        $query = Ticket::with(["user", "location", "room", "assignedTo"]);

        // Применение фильтров
        if ($request->filled("status")) {
            $query->where("status", $request->get("status"));
        }

        if ($request->filled("category")) {
            $query->where("category", $request->get("category"));
        }

        if ($request->filled("priority")) {
            $query->where("priority", $request->get("priority"));
        }

        if ($request->filled("location_id")) {
            $query->where("location_id", $request->get("location_id"));
        }

        if ($request->filled("room_id")) {
            $query->where("room_id", $request->get("room_id"));
        }

        if ($request->filled("assigned_to")) {
            $query->where("assigned_to_id", $request->get("assigned_to"));
        }

        if ($request->filled("search")) {
            $search = $request->get("search");
            $query->where(function ($q) use ($search) {
                $q->where("title", "like", "%{$search}%")
                    ->orWhere("reporter_name", "like", "%{$search}%")
                    ->orWhere("reporter_phone", "like", "%{$search}%")
                    ->orWhere("description", "like", "%{$search}%");
            });
        }

        // Сортировка по дате создания (новые сверху)
        $tickets = $query->latest()->paginate(15)->withQueryString();

        // Получение данных для фильтров
        $locations = Cache::remember("locations_list", 3600, function () {
            return Location::select("id", "name")->orderBy("name")->get();
        });

        $assignable = User::whereHas("role", function ($q) {
            $q->whereIn("slug", ["admin", "master", "technician"]);
        })
            ->select("id", "name")
            ->get();

        // Получение категорий из уникальных значений
        $categories = Ticket::select("category")
            ->distinct()
            ->whereNotNull("category")
            ->pluck("category")
            ->sort();

        // Получение кабинетов
        $rooms = Cache::remember("rooms_list", 3600, function () {
            return \App\Models\Room::active()
                ->select("id", "number", "name", "type", "building", "floor")
                ->orderBy("number")
                ->get();
        });

        return view(
            "tickets.all",
            compact(
                "tickets",
                "locations",
                "assignable",
                "categories",
                "rooms",
            ),
        );
    }

    /**
     * API для получения заявок (для динамического обновления)
     */
    public function api(Request $request)
    {
        // Проверка аутентификации и роли
        if (
            !Auth::check() ||
            !in_array(optional(Auth::user()->role)->slug, [
                "admin",
                "master",
                "technician",
            ])
        ) {
            abort(403);
        }
        $query = Ticket::with(["user", "location", "room", "assignedTo"]);

        // Применение фильтров
        if ($request->filled("status")) {
            $query->where("status", $request->get("status"));
        }

        if ($request->filled("category")) {
            $query->where("category", $request->get("category"));
        }

        if ($request->filled("priority")) {
            $query->where("priority", $request->get("priority"));
        }

        if ($request->filled("location_id")) {
            $query->where("location_id", $request->get("location_id"));
        }

        if ($request->filled("room_id")) {
            $query->where("room_id", $request->get("room_id"));
        }

        if ($request->filled("assigned_to")) {
            $query->where("assigned_to_id", $request->get("assigned_to"));
        }

        if ($request->filled("search")) {
            $search = $request->get("search");
            $query->where(function ($q) use ($search) {
                $q->where("title", "like", "%{$search}%")
                    ->orWhere("reporter_name", "like", "%{$search}%")
                    ->orWhere("reporter_phone", "like", "%{$search}%")
                    ->orWhere("description", "like", "%{$search}%");
            });
        }

        // Получение заявок с ограничением количества
        $query = $query->latest();

        if ($request->filled("limit")) {
            $limit = min((int) $request->get("limit"), 100); // Максимум 100 заявок
            $tickets = $query->take($limit)->get();
        } else {
            $tickets = $query->get();
        }

        // Подготовка данных для JSON
        $ticketsData = $tickets->map(function ($ticket) {
            return [
                "id" => $ticket->id,
                "title" => $ticket->title,
                "description" => $ticket->description,
                "status" => $ticket->status,
                "priority" => $ticket->priority,
                "category" => $ticket->category,
                "reporter_name" => $ticket->reporter_name,
                "reporter_phone" => $ticket->reporter_phone,
                "location" => $ticket->location
                    ? $ticket->location->name
                    : null,
                "assigned_to" => $ticket->assignedTo
                    ? $ticket->assignedTo->name
                    : null,
                "room" => $ticket->room
                    ? [
                        "number" => $ticket->room->number,
                        "name" =>
                            $ticket->room->name ?? $ticket->room->type_name,
                        "full_address" => $ticket->room->full_address,
                    ]
                    : null,
                "location_name" => $ticket->location
                    ? $ticket->location->name
                    : null,
                "created_at" => $ticket->created_at->format("d.m.Y H:i"),
                "updated_at" => $ticket->updated_at->format("d.m.Y H:i"),
                "url" => route("tickets.show", $ticket),
            ];
        });

        // Статистика
        $stats = [
            "total" => $tickets->count(),
            "open" => $tickets->where("status", "open")->count(),
            "in_progress" => $tickets->where("status", "in_progress")->count(),
            "resolved" => $tickets->where("status", "resolved")->count(),
            "closed" => $tickets->where("status", "closed")->count(),
        ];

        return response()->json([
            "tickets" => $ticketsData,
            "stats" => $stats,
            "last_updated" => now()->format("d.m.Y H:i:s"),
        ]);
    }

    /**
     * Быстрое назначение заявки
     */
    public function quickAssign(Request $request, Ticket $ticket)
    {
        // Проверка аутентификации и роли
        if (
            !Auth::check() ||
            !in_array(optional(Auth::user()->role)->slug, [
                "admin",
                "master",
                "technician",
            ])
        ) {
            abort(403);
        }
        $data = $request->validate([
            "assigned_to_id" => "nullable|exists:users,id",
        ]);

        $ticket->update(["assigned_to_id" => $data["assigned_to_id"] ?? null]);

        return response()->json([
            "success" => true,
            "message" => "Заявка назначена",
            "assigned_to" => $ticket->assignedTo
                ? $ticket->assignedTo->name
                : null,
        ]);
    }

    /**
     * Быстрое изменение статуса заявки
     */
    public function quickStatus(Request $request, Ticket $ticket)
    {
        // Проверка аутентификации и роли
        if (
            !Auth::check() ||
            !in_array(optional(Auth::user()->role)->slug, [
                "admin",
                "master",
                "technician",
            ])
        ) {
            abort(403);
        }
        $data = $request->validate([
            "status" => "required|in:open,in_progress,resolved,closed",
        ]);

        $ticket->update(["status" => $data["status"]]);

        return response()->json([
            "success" => true,
            "message" => "Статус заявки изменен",
            "status" => $ticket->status,
        ]);
    }

    /**
     * API для получения только статистики заявок
     */
    public function stats(Request $request)
    {
        // Проверка аутентификации и роли
        if (
            !Auth::check() ||
            !in_array(optional(Auth::user()->role)->slug, [
                "admin",
                "master",
                "technician",
            ])
        ) {
            abort(403);
        }
        $query = Ticket::query();

        // Применение фильтров
        if ($request->filled("status")) {
            $query->where("status", $request->get("status"));
        }

        if ($request->filled("category")) {
            $query->where("category", $request->get("category"));
        }

        if ($request->filled("priority")) {
            $query->where("priority", $request->get("priority"));
        }

        if ($request->filled("location_id")) {
            $query->where("location_id", $request->get("location_id"));
        }

        if ($request->filled("room_id")) {
            $query->where("room_id", $request->get("room_id"));
        }

        if ($request->filled("assigned_to")) {
            $query->where("assigned_to_id", $request->get("assigned_to"));
        }

        if ($request->filled("search")) {
            $search = $request->get("search");
            $query->where(function ($q) use ($search) {
                $q->where("title", "like", "%{$search}%")
                    ->orWhere("reporter_name", "like", "%{$search}%")
                    ->orWhere("reporter_phone", "like", "%{$search}%")
                    ->orWhere("description", "like", "%{$search}%");
            });
        }

        $tickets = $query->get();

        // Статистика
        $stats = [
            "total" => $tickets->count(),
            "open" => $tickets->where("status", "open")->count(),
            "in_progress" => $tickets->where("status", "in_progress")->count(),
            "resolved" => $tickets->where("status", "resolved")->count(),
            "closed" => $tickets->where("status", "closed")->count(),
            "high_priority" => $tickets->where("priority", "high")->count(),
            "unassigned" => $tickets->whereNull("assigned_to_id")->count(),
        ];

        return response()->json([
            "stats" => $stats,
            "last_updated" => now()->format("d.m.Y H:i:s"),
        ]);
    }
}
