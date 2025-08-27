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

class UserController extends Controller
{
    // Middleware настраивается в маршрутах

    /**
     * Список всех пользователей
     */
    public function index(Request $request)
    {
        $query = User::with("role")->orderBy("created_at", "desc");

        // Фильтрация по статусу
        if ($request->filled("status")) {
            if ($request->status === "active") {
                $query->where("is_active", true);
            } elseif ($request->status === "inactive") {
                $query->where("is_active", false);
            }
        }

        // Фильтрация по роли
        if ($request->filled("role_id")) {
            $query->where("role_id", $request->role_id);
        }

        // Поиск по имени или телефону
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

        $users = $query->paginate(15);
        $roles = Role::all();

        return view("user.index", compact("users", "roles"));
    }

    /**
     * Форма создания пользователя
     */
    public function create()
    {
        $roles = Role::all();
        return view("user.create", compact("roles"));
    }

    /**
     * Сохранение нового пользователя
     */
    public function store(Request $request)
    {
        $messages = [
            "name.required" => "Пожалуйста, укажите имя пользователя",
            "name.max" => "Имя пользователя не должно превышать 255 символов",
            "phone.required" => "Пожалуйста, укажите номер телефона",
            "phone.max" => "Номер телефона не должен превышать 20 символов",
            "phone.unique" =>
                "Пользователь с таким номером телефона уже существует",
            "role_id.required" => "Пожалуйста, выберите роль пользователя",
            "role_id.exists" => "Выбранная роль не существует в системе",
            "password.required" => "Пожалуйста, укажите пароль",
            "password.min" => "Пароль должен содержать не менее 8 символов",
            "password.confirmed" => "Пароли не совпадают",
        ];

        $validator = Validator::make(
            $request->all(),
            [
                "name" => "required|string|max:255",
                "phone" => "required|string|max:20|unique:users",
                "role_id" => "required|exists:roles,id",
                "password" => "required|string|min:8|confirmed",
                "is_active" => "boolean",
            ],
            $messages,
        );

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user = User::create([
            "name" => $request->name,
            "phone" => $request->phone,
            "role_id" => $request->role_id,
            "password" => Hash::make($request->password),
            "is_active" => $request->boolean("is_active", true),
        ]);

        return redirect()
            ->route("user.index")
            ->with("success", "Пользователь успешно создан");
    }

    /**
     * Просмотр пользователя
     */
    public function show(User $user)
    {
        $user->load(["role", "tickets", "tickets.comments"]);

        // Статистика пользователя
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
        ];

        return view("user.show", compact("user", "stats"));
    }

    /**
     * Форма редактирования пользователя
     */
    public function edit(User $user)
    {
        $roles = Role::all();
        return view("user.edit", compact("user", "roles"));
    }

    /**
     * Обновление пользователя
     */
    public function update(Request $request, User $user)
    {
        $messages = [
            "name.required" => "Пожалуйста, укажите имя пользователя",
            "name.max" => "Имя пользователя не должно превышать 255 символов",
            "phone.required" => "Пожалуйста, укажите номер телефона",
            "phone.max" => "Номер телефона не должен превышать 20 символов",
            "phone.unique" =>
                "Пользователь с таким номером телефона уже существует",
            "role_id.required" => "Пожалуйста, выберите роль пользователя",
            "role_id.exists" => "Выбранная роль не существует в системе",
        ];

        $validator = Validator::make(
            $request->all(),
            [
                "name" => "required|string|max:255",
                "phone" => [
                    "required",
                    "string",
                    "max:20",
                    Rule::unique("users")->ignore($user->id),
                ],
                "role_id" => "required|exists:roles,id",
                "is_active" => "boolean",
            ],
            $messages,
        );

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user->update([
            "name" => $request->name,
            "phone" => $request->phone,
            "role_id" => $request->role_id,
            "is_active" => $request->boolean("is_active"),
        ]);

        return redirect()
            ->route("user.index")
            ->with("success", "Пользователь успешно обновлен");
    }

    /**
     * Удаление пользователя
     */
    public function destroy(User $user)
    {
        // Проверяем, не пытается ли пользователь удалить сам себя
        if ($user->id === auth()->id()) {
            return redirect()
                ->route("user.index")
                ->with("error", "Нельзя удалить собственную учетную запись");
        }

        // Проверяем, есть ли у пользователя активные заявки
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

        return redirect()
            ->route("user.index")
            ->with("success", "Пользователь успешно удален");
    }

    /**
     * Сброс пароля пользователя
     */
    public function resetPassword(Request $request, User $user)
    {
        $messages = [
            "new_password.required" => "Пожалуйста, укажите новый пароль",
            "new_password.min" =>
                "Новый пароль должен содержать не менее 8 символов",
            "new_password.confirmed" => "Пароли не совпадают",
        ];

        $validator = Validator::make(
            $request->all(),
            [
                "new_password" => "required|string|min:8|confirmed",
            ],
            $messages,
        );

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user->update([
            "password" => Hash::make($request->new_password),
        ]);

        return redirect()
            ->back()
            ->with("success", "Пароль пользователя успешно сброшен");
    }

    /**
     * Изменение статуса активности пользователя
     */
    public function toggleStatus(User $user)
    {
        // Проверяем, не пытается ли пользователь деактивировать сам себя
        if ($user->id === auth()->id()) {
            return redirect()
                ->route("user.index")
                ->with(
                    "error",
                    "Нельзя деактивировать собственную учетную запись",
                );
        }

        $wasActive = $user->is_active;

        $user->update([
            "is_active" => !$user->is_active,
        ]);

        $status = $user->is_active ? "активирована" : "деактивирована";

        // Если пользователь был активирован, отправляем уведомление с временным паролем
        if (!$wasActive && $user->is_active) {
            // Генерируем временный пароль
            $temporaryPassword = Str::random(10);

            // Обновляем пароль пользователя
            $user->update([
                "password" => Hash::make($temporaryPassword),
            ]);

            // Отправляем уведомление с данными для входа
            $user->notify(
                new AccountActivationNotification($temporaryPassword),
            );
        }

        return redirect()
            ->route("user.index")
            ->with(
                "success",
                "Учетная запись пользователя {$user->name} {$status}",
            );
    }

    /**
     * Массовые операции с пользователями
     */
    public function bulkAction(Request $request)
    {
        $messages = [
            "action.required" => "Пожалуйста, выберите действие",
            "action.in" => "Выбрано недопустимое действие",
            "user_ids.required" => "Пожалуйста, выберите пользователей",
            "user_ids.array" => "Некорректный формат списка пользователей",
            "user_ids.*.exists" =>
                "Один или несколько выбранных пользователей не существуют",
            "new_role_id.required_if" =>
                "Для изменения роли необходимо выбрать новую роль",
            "new_role_id.exists" => "Выбранная роль не существует",
        ];

        $validator = Validator::make(
            $request->all(),
            [
                "action" =>
                    "required|in:activate,deactivate,delete,change_role",
                "user_ids" => "required|array",
                "user_ids.*" => "exists:users,id",
                "new_role_id" =>
                    "required_if:action,change_role|exists:roles,id",
            ],
            $messages,
        );

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        $userIds = $request->user_ids;
        $currentUserId = auth()->id();

        // Убираем текущего пользователя из списка
        $userIds = array_filter($userIds, function ($id) use ($currentUserId) {
            return $id != $currentUserId;
        });

        if (empty($userIds)) {
            return redirect()
                ->back()
                ->with("error", "Нет пользователей для обработки");
        }

        $users = User::whereIn("id", $userIds);

        switch ($request->action) {
            case "activate":
                // Активируем пользователей и отправляем им уведомления
                $users->get()->each(function ($user) {
                    if (!$user->is_active) {
                        // Генерируем временный пароль
                        $temporaryPassword = Str::random(10);

                        // Обновляем пароль и статус пользователя
                        $user->update([
                            "password" => Hash::make($temporaryPassword),
                            "is_active" => true,
                        ]);

                        // Отправляем уведомление с данными для входа
                        $user->notify(
                            new AccountActivationNotification(
                                $temporaryPassword,
                            ),
                        );
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
                $users->update(["role_id" => $request->new_role_id]);
                $message = "Роли пользователей успешно изменены";
                break;

            case "delete":
                // Проверяем, нет ли у пользователей активных заявок
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

                $users->delete();
                $message = "Пользователи успешно удалены";
                break;
        }

        return redirect()->route("user.index")->with("success", $message);
    }

    /**
     * Экспорт пользователей в CSV
     */
    public function export(Request $request)
    {
        $users = User::with("role")->get();

        $filename = "users_" . date("Y-m-d_H-i-s") . ".csv";

        $headers = [
            "Content-Type" => "text/csv",
            "Content-Disposition" => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($users) {
            $file = fopen("php://output", "w");

            // Заголовки CSV
            fputcsv($file, [
                "ID",
                "Имя",
                "Телефон",
                "Роль",
                "Статус",
                "Дата регистрации",
                "Последний вход",
            ]);

            // Данные пользователей
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

    /**
     * Статистика пользователей
     */
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
