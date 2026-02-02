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
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Http\Requests\StoreTicketCommentRequest;
use App\Http\Requests\AssignTicketRequest;
use App\Events\TicketCreated;
use App\Events\TicketStatusChanged;
use App\Events\TicketAssigned;
use App\Events\TicketCommentCreated;
use App\Services\CacheService;
use App\Traits\HasPagination;

class TicketController extends Controller
{
    use HasPagination;

    protected $notificationService;
    protected $cacheService;

    public function __construct(NotificationService $notificationService, CacheService $cacheService)
    {
        $this->notificationService = $notificationService;
        $this->cacheService = $cacheService;
    }
    
     * Display a listing of the resource.

    public function index(Request $request)
    {
        
        $query = Ticket::withFullTicketData();

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
                $q->where("reporter_phone", "like", "%{$search}%")
                    ->orWhere("title", "like", "%{$search}%")
                    ->orWhere("reporter_name", "like", "%{$search}%");
            });
        }

        
        $user = Auth::user();
        if ($user && $user->role) {
            if (in_array($user->role->slug, ["admin", "master"])) {
                
            } elseif ($user->role->slug === "technician") {
                
                $query->where(function ($q) use ($user) {
                    $q->where("user_id", $user->id)->orWhere(
                        "assigned_to_id",
                        $user->id,
                    );
                });
            } else {
                
                $query->where("user_id", $user->id);
            }
        }

        $tickets = $this->paginateQuery($query->latest(), $request, 'tickets');

        $locations = $this->cacheService->getLocations();
        $assignable = $this->cacheService->getAssignableUsers();
        $categories = $this->cacheService->getTicketCategories();

        return view(
            "tickets.index",
            compact("tickets", "locations", "assignable", "categories"),
        );
    }

    
     * Show the form for creating a new resource.

    public function create()
    {
        
        $rooms = $this->cacheService->getActiveRooms();

        
        $userResponsibleRoom = null;
        $user = Auth::user();
        if ($user) {
            $userResponsibleRoom = \App\Models\Room::where(
                "responsible_user_id",
                $user->id,
            )
                ->select("id", "number", "name")
                ->first();
        }

        return view("tickets.create", compact("rooms", "userResponsibleRoom"));
    }

    
     * Store a newly created resource in storage.

    public function store(StoreTicketRequest $request)
    {
        $data = $request->validated();

        
        $user = Auth::user();
        if ($user) {
            
            $data["reporter_name"] = $user->name;
            $data["reporter_phone"] = $user->phone;
        }

        $data["user_id"] = $user ? $user->id : null;
        $ticket = Ticket::create($data);

        
        event(new TicketCreated($ticket, $user));

        
        $this->notificationService->notifyNewTicket($ticket);

        return Redirect::route("tickets.show", $ticket)->with(
            "success",
            "Заявка создана",
        );
    }

    
     * Display the specified resource.

    public function show(Ticket $ticket)
    {
        
        try {
            
            $ticket->load([
                'user:id,name,phone,role_id',
                'user.role:id,name,slug',
                'location:id,name',
                'assignedTo:id,name,phone,role_id',
                'assignedTo.role:id,name,slug',
                'room:id,number,name,type,building,floor',
                'equipment:id,name,model,serial_number',
                'comments.user:id,name',
                'comments' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                }
            ]);

            $locations = $this->cacheService->getLocations();
            $assignable = $this->cacheService->getAssignableUsers();

            
            $categoryLabels = $this->cacheService->getTicketCategories();

            return view(
                "tickets.show",
                compact("ticket", "locations", "assignable", "categoryLabels"),
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error(
                "Ошибка при отображении тикета: " . $e->getMessage(),
            );
            return redirect()
                ->route("tickets.index")
                ->with(
                    "error",
                    "Не удалось отобразить данные заявки. Пожалуйста, попробуйте позднее.",
                );
        }
    }

    
     * Show the form for editing the specified resource.

    public function edit(Ticket $ticket)
    {
        if (!$this->canModify($ticket)) {
            abort(403);
        }
        return view("tickets.edit", compact("ticket"));
    }

    
     * Update the specified resource in storage.

    public function update(UpdateTicketRequest $request, Ticket $ticket)
    {
        if (!$this->canModify($ticket)) {
            abort(403);
        }

        $data = $request->validated();

        $ticket->update($data);
        return Redirect::route("tickets.show", $ticket)->with(
            "success",
            "Заявка обновлена",
        );
    }

    
     * Remove the specified resource from storage.

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

    
    public function start(Ticket $ticket)
    {
        if (!$this->canTakeTicketInWork($ticket)) {
            abort(403);
        }

        
        if ($ticket->status === "closed") {
            return Redirect::back()->with(
                "error",
                "Нельзя взять в работу закрытую заявку",
            );
        }

        $oldStatus = $ticket->status;
        $oldAssignedId = $ticket->assigned_to_id;
        
        
        $ticket->update([
            "status" => "in_progress",
            "assigned_to_id" => Auth::id()
        ]);

        
        event(new TicketStatusChanged($ticket, $oldStatus, "in_progress", Auth::user()));

        
        if ($oldAssignedId !== Auth::id()) {
            event(new TicketAssigned($ticket, Auth::user(), Auth::user()));
        }

        
        $this->notificationService->notifyTicketStatusChanged(
            $ticket,
            $oldStatus,
            "in_progress",
        );

        
        $commentContent = "Заявка взята в работу";
        if ($oldAssignedId !== Auth::id()) {
            $commentContent .= " и назначена на " . Auth::user()->name;
        }
        
        TicketComment::create([
            "ticket_id" => $ticket->id,
            "user_id" => Auth::id(),
            "content" => $commentContent,
            "is_system" => true,
        ]);

        return Redirect::back()->with("success", "Работа над заявкой начата");
    }

    
    public function resolve(Ticket $ticket)
    {
        if (!$this->canModify($ticket)) {
            abort(403);
        }

        
        if ($ticket->status === "closed") {
            return Redirect::back()->with(
                "error",
                "Нельзя отметить как решённую закрытую заявку",
            );
        }

        $oldStatus = $ticket->status;
        $ticket->update(["status" => "resolved"]);

        
        event(new TicketStatusChanged($ticket, $oldStatus, "resolved", Auth::user()));

        
        $this->notificationService->notifyTicketStatusChanged(
            $ticket,
            $oldStatus,
            "resolved",
        );

        
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

    
    public function close(Ticket $ticket)
    {
        if (!$this->canModify($ticket)) {
            abort(403);
        }

        
        if (!$ticket->assigned_to_id) {
            return Redirect::back()->with(
                "error",
                "Нельзя закрыть заявку без назначенного исполнителя",
            );
        }

        $oldStatus = $ticket->status;
        $ticket->update(["status" => "closed"]);

        
        event(new TicketStatusChanged($ticket, $oldStatus, "closed", Auth::user()));

        
        $this->notificationService->notifyTicketStatusChanged(
            $ticket,
            $oldStatus,
            "closed",
        );

        
        TicketComment::create([
            "ticket_id" => $ticket->id,
            "user_id" => Auth::id(),
            "content" => "Заявка закрыта",
            "is_system" => true,
        ]);

        return Redirect::back()->with("success", "Заявка закрыта");
    }

    
    public function commentStore(StoreTicketCommentRequest $request, Ticket $ticket)
    {
        $data = $request->validated();

        $comment = TicketComment::create([
            "ticket_id" => $ticket->id,
            "user_id" => Auth::id(),
            "content" => $data["content"],
        ]);

        
        event(new TicketCommentCreated($comment, Auth::user()));

        return Redirect::route("tickets.show", $ticket)->with(
            "success",
            "Комментарий добавлен",
        );
    }

    
    public function assign(AssignTicketRequest $request, Ticket $ticket)
    {
        $this->authorizeAssign();

        
        if ($ticket->status === "closed") {
            return Redirect::back()->with(
                "error",
                "Нельзя назначить исполнителя на закрытую заявку",
            );
        }

        
        $data = $request->validated();
        $newAssignedId = $data["assigned_to_id"] ?? null;

        $oldAssignedId = $ticket->assigned_to_id;
        $ticket->update(["assigned_to_id" => $newAssignedId]);

        
        if ($newAssignedId && $newAssignedId !== $oldAssignedId) {
            $assignedUser = User::find($newAssignedId);
            if ($assignedUser) {
                
                event(new TicketAssigned($ticket, $assignedUser, Auth::user()));

                $this->notificationService->notifyTicketAssigned(
                    $ticket,
                    $assignedUser,
                );

                
                TicketComment::create([
                    "ticket_id" => $ticket->id,
                    "user_id" => Auth::id(),
                    "content" => "Заявка назначена на {$assignedUser->name}",
                    "is_system" => true,
                ]);
            }
        } elseif ($oldAssignedId && $newAssignedId === null) {
            
            $oldAssignedUser = User::find($oldAssignedId);
            if ($oldAssignedUser) {
                
                $this->notificationService->notifyTicketUnassigned(
                    $ticket,
                    $oldAssignedUser,
                );

                
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

    
     * Check whether current user can modify/manage ticket.

    private function canModify(Ticket $ticket): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }
        
        
        if ($user->role && in_array($user->role->slug, ["admin", "master"])) {
            return true;
        }
        
        
        if ($user->role && $user->role->slug === "technician" && $ticket->status !== "closed") {
            return true;
        }
        
        
        return ($ticket->user_id && $ticket->user_id === $user->id) || 
               ($ticket->reporter_id && $ticket->reporter_id === $user->id);
    }

    
     * Check whether current user can take ticket in work

    private function canTakeTicketInWork(Ticket $ticket): bool
    {
        $user = Auth::user();
        if (!$user || !$user->role) {
            return false;
        }

        
        if (in_array($user->role->slug, ["admin", "master", "technician"])) {
            return $this->canTakeInWork($ticket);
        }

        return false;
    }

    
     * Check if the ticket can be taken in work

    private function canTakeInWork(Ticket $ticket): bool
    {
        return $ticket->status !== "closed";
    }
}
