<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use App\Notifications\AccountActivationNotification;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\BulkUserActionRequest;
use App\Events\UserCreated;
use App\Events\UserStatusChanged;
use App\Services\CacheService;
use App\Traits\HasPagination;

class UserController extends Controller
{
    use HasPagination;

    protected $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    
     * Список всех пользователей

    public function index(Request $request)
    {
        $query = User::withFullUserData()
            ->withLimited('tickets', 5)
            ->orderBy("created_at", "desc");

        
        if ($request->filled("status")) {
            if ($request->status === "active") {
                $query->where("is_active", true);
            } elseif ($request->status === "inactive") {
                $query->where("is_active", false);
            }
        }

        
        if ($request->filled("role_id")) {
            $query->where("role_id", $request->role_id);
        }

        
        if ($request->filled("search")) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where("name", "like", "%{$search}%")->orWhere(
                    "phone",
                    "like",
                    "%{$search}%",
                );
            });
        }

        $users = $this->paginateQuery($query, $request, 'users');
        $roles = $this->cacheService->getRoles();

        return view("user.index", compact("users", "roles"));
    }

    
     * Форма создания пользователя

    public function create()
    {
        $roles = $this->cacheService->getRoles();
        return view("user.create", compact("roles"));
    }

    
     * Сохранение нового пользователя

    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();

        $user = User::create([
            "name" => $data["name"],
            "phone" => $data["phone"],
            "role_id" => $data["role_id"],
            "password" => Hash::make($data["password"]),
            "is_active" => $data["is_active"] ?? true,
        ]);

        
        event(new UserCreated($user, auth()->user()));

        
        $this->cacheService->clearUserRelatedCache();

        return redirect()
            ->route("user.index")
            ->with("success", "Пользователь успешно создан");
    }

    
     * Просмотр пользователя

    public function show(User $user)
    {
        $user->load([
            "role:id,name,slug",
            "tickets:id,user_id,title,status,priority,category,created_at",
            "tickets.comments:id,ticket_id,user_id,content,created_at",
            "assignedTickets:id,assigned_to_id,title,status,priority,category,created_at",
            "responsibleForRooms:id,responsible_user_id,number,name,type,building,floor"
        ]);

        
        $stats = [
            "total_tickets" => $user->tickets->count(),
            "open_tickets" => $user->tickets
                ->filter(function ($ticket) {
                    return $ticket->status === "open";
                })
                ->count(),
            "resolved_tickets" => $user->tickets
                ->filter(function ($ticket) {
                    return $ticket->status === "resolved";
                })
                ->count(),
            "total_comments" => $user->tickets->sum(function ($ticket) {
                return $ticket->comments->count();
            }),
            "assigned_tickets" => $user->assignedTickets->count(),
            "responsible_rooms" => $user->responsibleForRooms->count(),
        ];

        return view("user.show", compact("user", "stats"));
    }

    
     * Форма редактирования пользователя

    public function edit(User $user)
    {
        $roles = $this->cacheService->getRoles();
        return view("user.edit", compact("user", "roles"));
    }

    
     * Обновление пользователя

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();

        $user->update([
            "name" => $data["name"],
            "phone" => $data["phone"],
            "role_id" => $data["role_id"],
            "is_active" => $data["is_active"] ?? false,
        ]);

        
        $this->cacheService->clearUserRelatedCache();

        return redirect()
            ->route("user.index")
            ->with("success", "Пользователь успешно обновлен");
    }

    
     * Удаление пользователя

    public function destroy(User $user)
    {
        
        if ($user->id === auth()->id()) {
            return redirect()
                ->route("user.index")
                ->with("error", "Нельзя удалить собственную учетную запись");
        }

        
        if (
            $user
                ->tickets()
                ->whereIn("status", ["open", "in_progress"])
                ->exists()
        ) {
            return redirect()
                ->route("user.index")
                ->with(
                    "error",
                    "Нельзя удалить пользователя с активными заявками",
                );
        }

        $user->delete();

        
        $this->cacheService->clearUserRelatedCache();

        return redirect()
            ->route("user.index")
            ->with("success", "Пользователь успешно удален");
    }

    
     * Сброс пароля пользователя

    public function resetPassword(ResetPasswordRequest $request, User $user)
    {
        $data = $request->validated();

        $user->update([
            "password" => Hash::make($data["new_password"]),
        ]);

        return redirect()
            ->back()
            ->with("success", "Пароль пользователя успешно сброшен");
    }

    
     * Изменение статуса активности пользователя

    public function toggleStatus(User $user)
    {
        
        if ($user->id === auth()->id()) {
            return redirect()
                ->route("user.index")
                ->with(
                    "error",
                    "Нельзя деактивировать собственную учетную запись",
                );
        }

        $wasActive = $user->is_active;
        $newStatus = !$user->is_active;

        $user->update([
            "is_active" => $newStatus,
        ]);

        
        event(new UserStatusChanged($user, $wasActive, $newStatus, auth()->user()));

        
        $this->cacheService->clearUserRelatedCache();

        $status = $user->is_active ? "активирована" : "деактивирована";

        
        if (!$wasActive && $user->is_active) {
            
            $user->notify(new AccountActivationNotification());
        }

        return redirect()
            ->route("user.index")
            ->with(
                "success",
                "Учетная запись пользователя {$user->name} {$status}",
            );
    }

    
     * Массовые операции с пользователями

    public function bulkAction(BulkUserActionRequest $request)
    {
        $data = $request->validated();

        $userIds = $data["user_ids"];
        $currentUserId = auth()->id();

        
        $userIds = array_filter($userIds, function ($id) use ($currentUserId) {
            return $id != $currentUserId;
        });

        if (empty($userIds)) {
            return redirect()
                ->back()
                ->with("error", "Нет пользователей для обработки");
        }

        $users = User::whereIn("id", $userIds);

        switch ($data["action"]) {
            case "activate":
                
                $users->get()->each(function ($user) {
                    if (!$user->is_active) {
                        
                        $user->update([
                            "is_active" => true,
                        ]);

                        
                        $user->notify(new AccountActivationNotification());
                    } else {
                        $user->update(["is_active" => true]);
                    }
                });
                $message = "Пользователи успешно активированы";
                break;

            case "deactivate":
                $users->update(["is_active" => false]);
                $message = "Пользователи успешно деактивированы";
                break;

            case "change_role":
                $users->update(["role_id" => $data["new_role_id"]]);
                $message = "Роли пользователей успешно изменены";
                break;

            case "delete":
                
                $usersWithActiveTickets = User::whereIn("id", $userIds)
                    ->whereHas("tickets", function ($query) {
                        $query->whereIn("status", ["open", "in_progress"]);
                    })
                    ->pluck("name")
                    ->toArray();

                if (!empty($usersWithActiveTickets)) {
                    return redirect()
                        ->back()
                        ->with(
                            "error",
                            "Следующие пользователи имеют активные заявки и не могут быть удалены: " .
                                implode(", ", $usersWithActiveTickets),
                        );
                }

                
                $usersAssignedToActiveTickets = User::whereIn("id", $userIds)
                    ->whereHas("assignedTickets", function ($query) {
                        $query->whereIn("status", ["open", "in_progress"]);
                    })
                    ->pluck("name")
                    ->toArray();

                if (!empty($usersAssignedToActiveTickets)) {
                    return redirect()
                        ->back()
                        ->with(
                            "error",
                            "Следующие пользователи назначены исполнителями активных заявок и не могут быть удалены: " .
                                implode(", ", $usersAssignedToActiveTickets),
                        );
                }

                $users->delete();
                $message = "Пользователи успешно удалены";
                break;
        }

        return redirect()->route("user.index")->with("success", $message);
    }

    
     * Экспорт пользователей в CSV

    public function export(Request $request)
    {
        $users = User::with("role")->get();

        $filename = "users_" . date("Y-m-d_H-i-s") . ".csv";

        $headers = [
            "Content-Type" => "text/csv",
            "Content-Disposition" => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($users) {
            $file = fopen("php:

            
            fputcsv($file, [
                "ID",
                "Имя",
                "Телефон",
                "Роль",
                "Статус",
                "Дата регистрации",
                "Последний вход",
            ]);

            
            foreach ($users as $user) {
                fputcsv($file, [
                    $user->id,
                    $user->name,
                    $user->phone,
                    $user->role ? $user->role->name : "Не назначена",
                    $user->is_active ? "Активен" : "Неактивен",
                    $user->created_at->format("d.m.Y H:i"),
                    $user->last_login_at
                        ? $user->last_login_at->format("d.m.Y H:i")
                        : "Никогда",
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    
     * Статистика пользователей

    public function statistics()
    {
        $stats = [
            "total_users" => User::count(),
            "active_users" => User::where("is_active", true)->count(),
            "inactive_users" => User::where("is_active", false)->count(),
            "users_by_role" => User::with("role")
                ->get()
                ->groupBy("role.name")
                ->map(function ($users) {
                    return $users->count();
                }),
            "recent_registrations" => User::where(
                "created_at",
                ">=",
                now()->subDays(30),
            )->count(),
            "users_with_tickets" => User::whereHas("tickets")->count(),
        ];

        return view("user.statistics", compact("stats"));
    }
}
