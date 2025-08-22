<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use App\Models\TicketComment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Cache;
use App\Models\Location;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class TicketController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Список заявок с фильтрами и пагинацией
        $query = Ticket::with(["user", "location", "assignedTo"]);

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

        if ($request->filled("assigned_to")) {
            $query->where("assigned_to_id", $request->get("assigned_to"));
        }

        if ($request->filled("search")) {
            $search = $request->get("search");
            $query->where(function ($q) use ($search) {
                $q->where("reporter_email", "like", "%{$search}%")->orWhere(
                    "title",
                    "like",
                    "%{$search}%",
                );
            });
        }

        // Если пользователь не админ/мастер — показываем только его заявки
        $user = Auth::user();
        if (
            $user &&
            !($user->role && in_array($user->role->slug, ["admin", "master"]))
        ) {
            $query->where("user_id", $user->id);
        }

        $tickets = $query->latest()->paginate(10)->withQueryString();

        $locations = Cache::remember("locations_list", 3600, function () {
            return Location::select("id", "name")->orderBy("name")->get();
        });

        $assignable = User::whereHas("role", function ($q) {
            $q->whereIn("slug", ["admin", "master", "technician"]);
        })
            ->select("id", "name")
            ->get();

        return view(
            "tickets.index",
            compact("tickets", "locations", "assignable"),
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Форма создания заявки
        $rooms = Cache::remember("rooms_list", 3600, function () {
            return \App\Models\Room::active()
                ->select("id", "number", "name", "type", "building", "floor")
                ->orderBy("number")
                ->get();
        });
        return view("tickets.create", compact("rooms"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            "title" => "required|string|max:255",
            "category" => "required|string",
            "priority" => "required|string",
            "description" => "required|string",
            "reporter_name" => "nullable|string|max:255",
            "reporter_id" => "nullable|string|max:50",
            "reporter_email" => "nullable|email|max:255",
            "location_id" => "nullable|exists:locations,id",
            "room_id" => "nullable|exists:rooms,id",
            "equipment_id" => "nullable|exists:equipment,id",
        ]);

        // Автозаполняем reporter данные из текущего пользователя, если не указано
        $user = Auth::user();
        if ($user) {
            if (empty($data["reporter_name"])) {
                $data["reporter_name"] = $user->name;
            }
            if (empty($data["reporter_email"])) {
                $data["reporter_email"] = $user->email;
            }
        }

        $data["user_id"] = $user ? $user->id : null;
        $ticket = Ticket::create($data);

        // Отправляем уведомление о новой заявке
        $this->notificationService->notifyNewTicket($ticket);

        return Redirect::route("tickets.show", $ticket)->with(
            "success",
            "Заявка создана",
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Ticket $ticket)
    {
        // Просмотр отдельной заявки
        $locations = Cache::remember("locations_list", 3600, function () {
            return Location::select("id", "name")->orderBy("name")->get();
        });

        $assignable = User::whereHas("role", function ($q) {
            $q->whereIn("slug", ["admin", "master", "technician"]);
        })
            ->select("id", "name")
            ->get();

        return view(
            "tickets.show",
            compact("ticket", "locations", "assignable"),
        );
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Ticket $ticket)
    {
        if (!$this->canModify($ticket)) {
            abort(403);
        }
        return view("tickets.edit", compact("ticket"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Ticket $ticket)
    {
        if (!$this->canModify($ticket)) {
            abort(403);
        }

        $data = $request->validate([
            "title" => "required|string|max:255",
            "category" => "required|string",
            "priority" => "required|string",
            "description" => "required|string",
            "reporter_name" => "nullable|string|max:255",
            "reporter_id" => "nullable|string|max:50",
            "reporter_email" => "nullable|email|max:255",
            "location_id" => "nullable|exists:locations,id",
            "room_id" => "nullable|exists:rooms,id",
            "equipment_id" => "nullable|exists:equipment,id",
            "assigned_to_id" => "nullable|exists:users,id",
        ]);

        $ticket->update($data);
        return Redirect::route("tickets.show", $ticket)->with(
            "success",
            "Заявка обновлена",
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ticket $ticket)
    {
        if (!$this->canModify($ticket)) {
            abort(403);
        }
        $ticket->delete();
        return Redirect::route("tickets.index")->with(
            "success",
            "Заявка удалена",
        );
    }

    // Переход в работу
    public function start(Ticket $ticket)
    {
        if (!$this->canModify($ticket)) {
            abort(403);
        }

        $oldStatus = $ticket->status;
        $ticket->update(["status" => "in_progress"]);

        // Отправляем уведомление об изменении статуса
        $this->notificationService->notifyTicketStatusChanged(
            $ticket,
            $oldStatus,
            "in_progress",
        );

        // Добавляем комментарий о смене статуса
        TicketComment::create([
            "ticket_id" => $ticket->id,
            "user_id" => Auth::id(),
            "content" => "Заявка взята в работу",
            "is_system" => true,
        ]);

        return Redirect::back()->with("success", "Работа над заявкой начата");
    }

    // Отметить как решённую
    public function resolve(Ticket $ticket)
    {
        if (!$this->canModify($ticket)) {
            abort(403);
        }

        $oldStatus = $ticket->status;
        $ticket->update(["status" => "resolved"]);

        // Отправляем уведомление об изменении статуса
        $this->notificationService->notifyTicketStatusChanged(
            $ticket,
            $oldStatus,
            "resolved",
        );

        // Добавляем комментарий о смене статуса
        TicketComment::create([
            "ticket_id" => $ticket->id,
            "user_id" => Auth::id(),
            "content" => "Заявка отмечена как решённая",
            "is_system" => true,
        ]);

        return Redirect::back()->with(
            "success",
            "Заявка отмечена как решённая",
        );
    }

    // Закрыть
    public function close(Ticket $ticket)
    {
        if (!$this->canModify($ticket)) {
            abort(403);
        }

        $oldStatus = $ticket->status;
        $ticket->update(["status" => "closed"]);

        // Отправляем уведомление об изменении статуса
        $this->notificationService->notifyTicketStatusChanged(
            $ticket,
            $oldStatus,
            "closed",
        );

        // Добавляем комментарий о смене статуса
        TicketComment::create([
            "ticket_id" => $ticket->id,
            "user_id" => Auth::id(),
            "content" => "Заявка закрыта",
            "is_system" => true,
        ]);

        return Redirect::back()->with("success", "Заявка закрыта");
    }

    // Добавить комментарий
    public function commentStore(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            "content" => "required|string",
        ]);

        $comment = TicketComment::create([
            "ticket_id" => $ticket->id,
            "user_id" => Auth::id(),
            "content" => $data["content"],
        ]);

        return Redirect::route("tickets.show", $ticket)->with(
            "success",
            "Комментарий добавлен",
        );
    }

    // Назначить заявку пользователю (ассайн)
    public function assign(Request $request, Ticket $ticket)
    {
        $this->authorizeAssign();

        $data = $request->validate([
            "assigned_to_id" => "nullable|exists:users,id",
        ]);

        $oldAssignedId = $ticket->assigned_to_id;
        $ticket->update(["assigned_to_id" => $data["assigned_to_id"] ?? null]);

        // Отправляем уведомление о назначении, если заявка была назначена новому пользователю
        if (
            $data["assigned_to_id"] &&
            $data["assigned_to_id"] !== $oldAssignedId
        ) {
            $assignedUser = User::find($data["assigned_to_id"]);
            if ($assignedUser) {
                $this->notificationService->notifyTicketAssigned(
                    $ticket,
                    $assignedUser,
                );

                // Добавляем комментарий о назначении
                TicketComment::create([
                    "ticket_id" => $ticket->id,
                    "user_id" => Auth::id(),
                    "content" => "Заявка назначена на {$assignedUser->name}",
                    "is_system" => true,
                ]);
            }
        }

        return Redirect::back()->with("success", "Заявка назначена");
    }

    private function authorizeAssign()
    {
        $user = Auth::user();
        if (
            !$user ||
            !(
                $user->role &&
                in_array($user->role->slug, ["admin", "master", "technician"])
            )
        ) {
            abort(403);
        }
    }

    private function canChangeStatus(Ticket $ticket): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }
        return $user->role &&
            in_array($user->role->slug, ["admin", "master", "technician"]);
    }

    /**
     * Check whether current user can modify/manage ticket.
     */
    private function canModify(Ticket $ticket): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }
        // Админ/мастер могут управлять всеми заявками
        if ($user->role && in_array($user->role->slug, ["admin", "master"])) {
            return true;
        }
        // Обычный пользователь — только свои
        return $ticket->user_id && $ticket->user_id === $user->id;
    }
}
