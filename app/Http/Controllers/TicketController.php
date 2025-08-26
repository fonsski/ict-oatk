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
                $q->where("reporter_email", "like", "%{$search}%")
                    ->orWhere("reporter_phone", "like", "%{$search}%")
                    ->orWhere("title", "like", "%{$search}%");
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
        $messages = [
            "title.required" => "Пожалуйста, укажите заголовок заявки",
            "title.min" => "Заголовок должен содержать не менее 5 символов",
            "title.max" => "Заголовок не должен превышать 255 символов",
            "category.required" => "Пожалуйста, выберите категорию заявки",
            "priority.required" => "Пожалуйста, выберите приоритет заявки",
            "description.required" => "Пожалуйста, добавьте описание проблемы",
            "description.min" =>
                "Описание должно содержать не менее 10 символов",
            "description.max" => "Описание не должно превышать 5000 символов",
            "location_id.exists" => "Выбранное местоположение не существует",
            "room_id.exists" => "Выбранный кабинет не существует",
            "equipment_id.exists" => "Выбранное оборудование не существует",
        ];

        $data = $request->validate(
            [
                "title" => "required|string|min:5|max:255",
                "category" => "required|string",
                "priority" => "required|string",
                "description" => "required|string|min:10|max:5000",
                "reporter_id" => "nullable|string|max:50",
                "location_id" => "nullable|exists:locations,id",
                "room_id" => "nullable|exists:rooms,id",
                "equipment_id" => "nullable|exists:equipment,id",
            ],
            $messages,
        );

        // Всегда используем данные авторизованного пользователя
        $user = Auth::user();
        if ($user) {
            // Всегда используем данные авторизованного пользователя независимо от ввода
            $data["reporter_name"] = $user->name;
            $data["reporter_email"] = $user->email;
            $data["reporter_phone"] = $user->phone;
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

        $messages = [
            "title.required" => "Пожалуйста, укажите заголовок заявки",
            "title.min" => "Заголовок должен содержать не менее 5 символов",
            "title.max" => "Заголовок не должен превышать 255 символов",
            "category.required" => "Пожалуйста, выберите категорию заявки",
            "priority.required" => "Пожалуйста, выберите приоритет заявки",
            "description.required" => "Пожалуйста, добавьте описание проблемы",
            "description.min" =>
                "Описание должно содержать не менее 10 символов",
            "description.max" => "Описание не должно превышать 5000 символов",
            "reporter_email.email" => "Пожалуйста, укажите корректный email",
            "reporter_phone.max" =>
                "Номер телефона не должен превышать 20 символов",
            "reporter_phone.regex" =>
                "Номер телефона должен быть в формате: +7 (999) 999-99-99",
            "location_id.exists" => "Выбранное местоположение не существует",
            "room_id.exists" => "Выбранный кабинет не существует",
            "equipment_id.exists" => "Выбранное оборудование не существует",
            "status.required" => "Пожалуйста, укажите статус заявки",
        ];

        $data = $request->validate(
            [
                "title" => "required|string|min:5|max:255",
                "category" => "required|string",
                "priority" => "required|string",
                "description" => "required|string|min:10|max:5000",
                "reporter_name" => "nullable|string|max:255",
                "reporter_id" => "nullable|string|max:50",
                "reporter_email" => "nullable|email|max:255",
                "reporter_phone" => [
                    "nullable",
                    "string",
                    "max:20",
                    "regex:/^\+7 \([0-9]{3}\) [0-9]{3}-[0-9]{2}-[0-9]{2}$/",
                ],
                "location_id" => "nullable|exists:locations,id",
                "room_id" => "nullable|exists:rooms,id",
                "equipment_id" => "nullable|exists:equipment,id",
                "status" => "nullable|string",
            ],
            $messages,
        );

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

        // Проверяем, можно ли взять заявку в работу
        if ($ticket->status === "closed") {
            return Redirect::back()->with(
                "error",
                "Нельзя взять в работу закрытую заявку",
            );
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

        // Проверяем, не закрыта ли заявка
        if ($ticket->status === "closed") {
            return Redirect::back()->with(
                "error",
                "Нельзя отметить как решённую закрытую заявку",
            );
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
        $messages = [
            "content.required" => "Пожалуйста, введите текст комментария",
            "content.min" => "Комментарий должен содержать не менее 2 символов",
            "content.max" => "Комментарий не должен превышать 1000 символов",
        ];

        $data = $request->validate(
            [
                "content" => "required|string|min:2|max:1000",
            ],
            $messages,
        );

        // Проверка на превышение длины перед сохранением
        if (strlen($data["content"]) > 1000) {
            return back()
                ->withErrors([
                    "content" =>
                        "Комментарий не должен превышать 1000 символов",
                ])
                ->withInput();
        }

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

        $messages = [
            "assigned_to_id.exists" =>
                "Выбранный исполнитель не существует в системе",
        ];

        $data = $request->validate(
            [
                "assigned_to_id" => "nullable|exists:users,id",
            ],
            $messages,
        );

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
        } elseif ($oldAssignedId && empty($data["assigned_to_id"])) {
            // Уведомление о снятии назначения
            $oldAssignedUser = User::find($oldAssignedId);
            if ($oldAssignedUser) {
                // Отправляем уведомление о снятии с заявки
                $this->notificationService->notifyTicketUnassigned(
                    $ticket,
                    $oldAssignedUser,
                );

                // Добавляем комментарий о снятии назначения
                TicketComment::create([
                    "ticket_id" => $ticket->id,
                    "user_id" => Auth::id(),
                    "content" => "Заявка снята с {$oldAssignedUser->name}",
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

    /**
     * Check if the ticket can be taken in work
     */
    private function canTakeInWork(Ticket $ticket): bool
    {
        return $ticket->status !== "closed";
    }
}
