<?php

use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\EquipmentCategoryController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\AllTicketsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\KnowledgeBaseController;
use App\Http\Controllers\KnowledgeCategoryController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\HomepageFAQController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ActivationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\DrawingCanvasController;
use App\Http\Controllers\TestCanvasController;
use Illuminate\Support\Facades\Route;

// Аутентификация

Route::middleware("guest")->group(function () {
    Route::get("login", [LoginController::class, "showLoginForm"])->name(
        "login",
    );
    Route::post("login", [LoginController::class, "login"]);
    Route::get("register", [
        RegisterController::class,
        "showRegistrationForm",
    ])->name("register");
    Route::post("register", [RegisterController::class, "register"]);

    // Маршруты для сброса пароля
    Route::get("password/reset", [
        ForgotPasswordController::class,
        "showLinkRequestForm",
    ])->name("password.request");
    Route::post("password/email", [
        ForgotPasswordController::class,
        "sendResetCode",
    ])->name("password.send");
    Route::get("password/code", [
        ForgotPasswordController::class,
        "showResetCodeForm",
    ])->name("password.code");
    Route::post("password/code", [
        ForgotPasswordController::class,
        "validateResetCode",
    ])->name("password.code.check");
    Route::get("password/reset/confirm", [
        ForgotPasswordController::class,
        "showResetForm",
    ])->name("password.reset");
    Route::post("password/reset", [
        ForgotPasswordController::class,
        "reset",
    ])->name("password.update");
});

Route::post("logout", [LoginController::class, "logout"])
    ->name("logout")
    ->middleware("auth");

// Тестовые маршруты для страниц ошибок (только для разработки)
Route::prefix("test-errors")
    ->middleware("auth")
    ->group(function () {
        Route::get("/", [
            \App\Http\Controllers\TestErrorController::class,
            "showErrors",
        ])->name("test.errors");
        Route::get("/404", [
            \App\Http\Controllers\TestErrorController::class,
            "test404",
        ])->name("test.404");
        Route::get("/403", [
            \App\Http\Controllers\TestErrorController::class,
            "test403",
        ])->name("test.403");
        Route::get("/401", [
            \App\Http\Controllers\TestErrorController::class,
            "test401",
        ])->name("test.401");
        Route::get("/500", [
            \App\Http\Controllers\TestErrorController::class,
            "test500",
        ])->name("test.500");
        Route::get("/503", [
            \App\Http\Controllers\TestErrorController::class,
            "test503",
        ])->name("test.503");
        Route::get("/419", [
            \App\Http\Controllers\TestErrorController::class,
            "test419",
        ])->name("test.419");
        Route::get("/429", [
            \App\Http\Controllers\TestErrorController::class,
            "test429",
        ])->name("test.429");
        Route::get("/custom", [
            \App\Http\Controllers\TestErrorController::class,
            "testCustom",
        ])->name("test.custom");
    });

Route::get("login/timeout", [LoginController::class, "timeout"])->name(
    "login.timeout",
);

// Домашняя страница
Route::get("/", [HomeController::class, "index"])->name("home");

// Статические страницы
Route::get("/terms", [PageController::class, "terms"])->name("terms");
Route::get("/privacy", [PageController::class, "privacy"])->name("privacy");

// API для главной страницы техника
Route::get("/home/technician/tickets", [
    HomeController::class,
    "technicianTicketsApi",
])
    ->name("home.technician.tickets")
    ->middleware("auth");

// Общедоступные маршруты (index/show will be registered after protected routes to avoid route collisions)

// Защищенные маршруты
Route::middleware("auth")->group(function () {
    // Маршруты для всех авторизованных пользователей
    Route::resource("/tickets", TicketController::class);
    // Дополнительные действия для заявок
    Route::post("/tickets/{ticket}/start", [
        TicketController::class,
        "start",
    ])->name("tickets.start");
    Route::post("/tickets/{ticket}/resolve", [
        TicketController::class,
        "resolve",
    ])->name("tickets.resolve");
    Route::post("/tickets/{ticket}/close", [
        TicketController::class,
        "close",
    ])->name("tickets.close");
    Route::post("/tickets/{ticket}/comments", [
        TicketController::class,
        "commentStore",
    ])->name("tickets.comment.store");
    Route::post("/tickets/{ticket}/assign", [
        TicketController::class,
        "assign",
    ])->name("tickets.assign");

    // Маршруты для всех заявок (только для admin, master, technician)
    Route::middleware([
        "auth",
        \App\Http\Middleware\CheckRole::class . ":admin,master,technician",
    ])->group(function () {
        Route::get("/all-tickets", [
            AllTicketsController::class,
            "index",
        ])->name("all-tickets.index");
        Route::get("/all-tickets/api", [
            AllTicketsController::class,
            "api",
        ])->name("all-tickets.api");
        Route::get("/all-tickets/stats", [
            AllTicketsController::class,
            "stats",
        ])->name("all-tickets.stats");
        Route::post("/all-tickets/{ticket}/quick-assign", [
            AllTicketsController::class,
            "quickAssign",
        ])->name("all-tickets.quick-assign");
        Route::post("/all-tickets/{ticket}/quick-status", [
            AllTicketsController::class,
            "quickStatus",
        ])->name("all-tickets.quick-status");
    });

    // Ресурс equipment полностью доступен только для ролей admin, master и technician
    Route::middleware([
        "auth",
        \App\Http\Middleware\CheckRole::class . ":admin,master,technician",
    ])->group(function () {
        Route::resource("/equipment", EquipmentController::class);
        Route::get("/equipment-search", [
            EquipmentController::class,
            "search",
        ])->name("equipment.search");

        // Маршруты для истории перемещений оборудования
        Route::get("/equipment/{equipment}/location-history", [
            EquipmentController::class,
            "locationHistory",
        ])->name("equipment.location.history");

        Route::get("/equipment/{equipment}/move", [
            EquipmentController::class,
            "moveForm",
        ])->name("equipment.move.form");

        Route::post("/equipment/{equipment}/move", [
            EquipmentController::class,
            "move",
        ])->name("equipment.move");

        // Маршруты для управления категориями оборудования
        Route::resource(
            "/equipment-categories",
            EquipmentCategoryController::class,
            [
                "as" => "equipment",
            ],
        );
    });

    // Управление пользователями (только для admin и master)
    Route::middleware([
        "auth",
        \App\Http\Middleware\CheckRole::class . ":admin,master",
    ])->group(function () {
        Route::resource("/user", UserController::class);

        // Дополнительные действия для пользователей
        Route::post("/user/{user}/reset-password", [
            UserController::class,
            "resetPassword",
        ])->name("user.reset-password");
        Route::post("/user/{user}/toggle-status", [
            UserController::class,
            "toggleStatus",
        ])->name("user.toggle-status");

        // Активация/деактивация учетной записи с отправкой email
        Route::post("/user/{user}/activate", [
            ActivationController::class,
            "activate",
        ])->name("user.activate");
        Route::post("/user/{user}/deactivate", [
            ActivationController::class,
            "deactivate",
        ])->name("user.deactivate");
        Route::post("/user/{user}/resend-activation", [
            ActivationController::class,
            "resendActivation",
        ])->name("user.resend-activation");
        Route::post("/user/bulk-action", [
            UserController::class,
            "bulkAction",
        ])->name("user.bulk-action");
        Route::get("/user/export", [UserController::class, "export"])->name(
            "user.export",
        );
        Route::get("/user/statistics", [
            UserController::class,
            "statistics",
        ])->name("user.statistics");
    });

    // Управление кабинетами (только для admin и master)
    Route::middleware([
        "auth",
        \App\Http\Middleware\CheckRole::class . ":admin,master",
    ])->group(function () {
        Route::resource("/room", RoomController::class);

        // Дополнительные действия для кабинетов
        Route::post("/room/{room}/change-status", [
            RoomController::class,
            "changeStatus",
        ])->name("room.change-status");
        Route::post("/room/{room}/toggle-active", [
            RoomController::class,
            "toggleActive",
        ])->name("room.toggle-active");
        Route::post("/room/bulk-action", [
            RoomController::class,
            "bulkAction",
        ])->name("room.bulk-action");
        Route::get("/room/export", [RoomController::class, "export"])->name(
            "room.export",
        );
        Route::get("/room/statistics", [
            RoomController::class,
            "statistics",
        ])->name("room.statistics");
        Route::get("/room/get-rooms", [
            RoomController::class,
            "getRooms",
        ])->name("room.get-rooms");
    });

    // Маршруты для администраторов, мастеров и техников
    Route::middleware([
        "auth",
        \App\Http\Middleware\CheckRole::class . ":admin,master,technician",
    ])->group(function () {
        Route::resource("/knowledge", KnowledgeBaseController::class)->except([
            "index",
            "show",
        ]);

        // Routes for knowledge categories management
        Route::resource(
            "/knowledge/categories",
            KnowledgeCategoryController::class,
        )->names([
            "index" => "knowledge.categories.index",
            "create" => "knowledge.categories.create",
            "store" => "knowledge.categories.store",
            "show" => "knowledge.categories.show",
            "edit" => "knowledge.categories.edit",
            "update" => "knowledge.categories.update",
            "destroy" => "knowledge.categories.destroy",
        ]);
    });

    // Управление FAQ главной страницы (только для admin и master)
    Route::middleware([
        "auth",
        \App\Http\Middleware\CheckRole::class . ":admin,master",
    ])->group(function () {
        Route::resource("/homepage-faq", HomepageFAQController::class)->except([
            "show",
        ]);
        Route::patch("/homepage-faq/{homepageFaq}/toggle-active", [
            HomepageFAQController::class,
            "toggleActive",
        ])->name("homepage-faq.toggle-active");
        Route::post("/homepage-faq/preview", [
            HomepageFAQController::class,
            "preview",
        ])->name("homepage-faq.preview");
        Route::post("/homepage-faq/upload-image", [
            HomepageFAQController::class,
            "uploadImage",
        ])->name("homepage-faq.upload-image");
    });

    // Группа маршрутов для работы с холстом для рисования
    // Доступна только администраторам, техникам и мастерам
    Route::middleware([
        "auth",
        \App\Http\Middleware\CheckRole::class . ":admin,technician,master",
    ])->group(function () {
        // Оставляем маршруты внутри группы, но они будут доступны только авторизованным пользователям
    });

    // Явные маршруты для инструмента рисования - доступны всем пользователям без middleware
    Route::get("/drawing-canvas", [
        DrawingCanvasController::class,
        "index",
    ])->name("drawing-canvas.index");
    Route::get("/drawing-canvas/create", [
        DrawingCanvasController::class,
        "create",
    ])->name("drawing-canvas.create");
    Route::post("/drawing-canvas", [
        DrawingCanvasController::class,
        "store",
    ])->name("drawing-canvas.store");
    Route::get("/drawing-canvas/{drawing_canvas}", [
        DrawingCanvasController::class,
        "show",
    ])->name("drawing-canvas.show");
    Route::get("/drawing-canvas/{drawing_canvas}/edit", [
        DrawingCanvasController::class,
        "edit",
    ])->name("drawing-canvas.edit");
    Route::put("/drawing-canvas/{drawing_canvas}", [
        DrawingCanvasController::class,
        "update",
    ])->name("drawing-canvas.update");
    Route::delete("/drawing-canvas/{drawing_canvas}", [
        DrawingCanvasController::class,
        "destroy",
    ])->name("drawing-canvas.destroy");

    // Test route for canvas controller
    Route::get("/test-canvas", [TestCanvasController::class, "test"])->name(
        "test-canvas",
    );

    // AJAX preview для markdown (только для авторизованных)
    Route::post("/knowledge/preview", [
        KnowledgeBaseController::class,
        "preview",
    ])->name("knowledge.preview");

    // Upload images for knowledge base articles
    Route::post("/knowledge/upload-image", [
        KnowledgeBaseController::class,
        "uploadImage",
    ])->name("knowledge.upload-image");

    // API Routes for notifications
    Route::prefix("api/notifications")->group(function () {
        Route::get("/", [NotificationController::class, "index"])->name(
            "api.notifications.index",
        );
        Route::get("/unread-count", [
            NotificationController::class,
            "unreadCount",
        ])->name("api.notifications.unread-count");
        Route::get("/poll", [NotificationController::class, "poll"])->name(
            "api.notifications.poll",
        );
        Route::get("/stats", [NotificationController::class, "stats"])->name(
            "api.notifications.stats",
        );
        Route::post("/mark-as-read/{notificationId}", [
            NotificationController::class,
            "markAsRead",
        ])->name("api.notifications.mark-as-read");
        Route::post("/mark-all-as-read", [
            NotificationController::class,
            "markAllAsRead",
        ])->name("api.notifications.mark-all-as-read");
    });

    // API для получения оборудования по кабинету
    Route::get("/api/equipment/by-room", [
        \App\Http\Controllers\Api\Equipment\RoomEquipmentController::class,
        "getByRoom",
    ])->name("api.equipment.by-room");

    // Test routes for notification system (only in development)

    // Маршруты для настроек
    Route::middleware("auth")->group(function () {});

    // Маршрут для просмотра документации
    Route::get("/docs/{docName}.md", [
        \App\Http\Controllers\SettingsController::class,
        "viewDocumentation",
    ])->name("docs.view");

    if (config("app.debug")) {
        Route::get("/test/debug", function () {
            $user = auth()->user();
            if (!$user) {
                return redirect()->route("login");
            }

            return view("test.notifications");
        })->name("test.debug");

        Route::get("/test/notifications", function () {
            $user = auth()->user();
            if (!$user) {
                return redirect()->route("login");
            }

            $notificationService = app(
                \App\Services\NotificationService::class,
            );

            // Create test notification
            $notificationService->createNotification([
                "user_id" => $user->id,
                "type" => "test",
                "title" => "Тестовое уведомление",
                "message" =>
                    "Это тестовое уведомление создано в " .
                    now()->format("H:i:s"),
                "data" => ["test" => true],
                "url" => route("home"),
            ]);

            return redirect()
                ->route("test.debug")
                ->with(
                    "success",
                    "Тестовое уведомление создано! Проверьте иконку уведомлений.",
                );
        })->name("test.notifications");

        Route::get("/test/ticket", function () {
            $user = auth()->user();
            if (!$user) {
                return redirect()->route("login");
            }

            $ticket = \App\Models\Ticket::create([
                "title" => "Тестовая заявка - " . now()->format("d.m.Y H:i:s"),
                "description" =>
                    "Это тестовая заявка для проверки системы уведомлений.",
                "category" => "testing",
                "priority" => "medium",
                "status" => "open",
                "reporter_name" => $user->name,
                "reporter_email" => $user->email,
                "user_id" => $user->id,
            ]);

            $notificationService = app(
                \App\Services\NotificationService::class,
            );
            $notificationService->notifyNewTicket($ticket);

            return redirect()
                ->route("test.debug")
                ->with(
                    "success",
                    "Тестовая заявка создана! Уведомления отправлены.",
                );
        })->name("test.ticket");

        Route::get("/test/api", function () {
            $user = auth()->user();
            if (!$user) {
                return response()->json(["error" => "Not authenticated"], 401);
            }

            $controller = new \App\Http\Controllers\HomeController();
            $response = $controller->technicianTicketsApi();

            return response()->json([
                "user" => [
                    "name" => $user->name,
                    "role" => $user->role ? $user->role->slug : null,
                ],
                "api_response_status" => $response->getStatusCode(),
                "api_data" => $response->getData(true),
                "can_manage_tickets" => user_can_manage_tickets(),
            ]);
        })->name("test.api");

        Route::get("/test/debug-auth", function () {
            $user = auth()->user();

            $debug = [
                "authenticated" => !!$user,
                "user_data" => $user
                    ? [
                        "id" => $user->id,
                        "name" => $user->name,
                        "email" => $user->email,
                        "is_active" => $user->is_active ?? "not set",
                        "role_relation" => !!$user->role,
                        "role_data" => $user->role
                            ? [
                                "id" => $user->role->id,
                                "name" => $user->role->name,
                                "slug" => $user->role->slug,
                            ]
                            : null,
                    ]
                    : null,
                "helper_functions" => [
                    "user_has_role_admin" => user_has_role("admin"),
                    "user_has_role_technician" => user_has_role("technician"),
                    "user_has_role_array" => user_has_role([
                        "admin",
                        "master",
                        "technician",
                    ]),
                    "user_can_manage_tickets" => user_can_manage_tickets(),
                    "user_can_manage_users" => user_can_manage_users(),
                    "user_is_technician" => user_is_technician(),
                ],
                "session_data" => [
                    "session_id" => session()->getId(),
                    "csrf_token" => csrf_token(),
                ],
                "request_info" => [
                    "ip" => request()->ip(),
                    "user_agent" => request()->userAgent(),
                    "route" => request()->route()
                        ? request()->route()->getName()
                        : null,
                ],
            ];

            return response()->json($debug, 200, [], JSON_PRETTY_PRINT);
        })->name("test.debug-auth");

        Route::get("/test/create-notification", function () {
            $user = auth()->user();
            if (!$user) {
                return response()->json(["error" => "Not authenticated"], 401);
            }

            $service = app(\App\Services\NotificationService::class);
            $service->createNotification([
                "user_id" => $user->id,
                "type" => "test",
                "title" => "Тестовое уведомление",
                "message" =>
                    "Это уведомление создано в веб-сессии в " .
                    now()->format("H:i:s"),
                "data" => ["test" => true, "session_id" => session()->getId()],
                "url" => route("home"),
            ]);

            $count = $service->getUnreadCount($user);
            $notifications = $service->getUserNotifications($user, 10);

            return response()->json([
                "message" => "Уведомление создано успешно!",
                "session_id" => session()->getId(),
                "unread_count" => $count,
                "total_notifications" => $notifications->count(),
                "notifications" => $notifications->toArray(),
            ]);
        })->name("test.create-notification");

        Route::get("/test/notifications-api", function () {
            $user = auth()->user();
            if (!$user) {
                return response()->json(["error" => "Not authenticated"], 401);
            }

            $debug = [];

            try {
                // Тест API index
                $controller = app(
                    \App\Http\Controllers\Api\NotificationController::class,
                );
                $request = new \Illuminate\Http\Request(["limit" => 10]);
                $response = $controller->index($request);
                $data = $response->getData(true);

                $debug["api_index"] = [
                    "status" => "success",
                    "data" => $data,
                ];
            } catch (Exception $e) {
                $debug["api_index"] = [
                    "status" => "error",
                    "error" => $e->getMessage(),
                ];
            }

            try {
                // Тест API unreadCount
                $controller = app(
                    \App\Http\Controllers\Api\NotificationController::class,
                );
                $response = $controller->unreadCount();
                $data = $response->getData(true);

                $debug["api_unread_count"] = [
                    "status" => "success",
                    "data" => $data,
                ];
            } catch (Exception $e) {
                $debug["api_unread_count"] = [
                    "status" => "error",
                    "error" => $e->getMessage(),
                ];
            }

            $debug["session_info"] = [
                "session_id" => session()->getId(),
                "user_id" => $user->id,
                "user_name" => $user->name,
                "session_notifications" => session(
                    "notifications.{$user->id}",
                    [],
                ),
            ];

            return response()->json($debug, 200, [], JSON_PRETTY_PRINT);
        })->name("test.notifications-api");
    }
});

Route::middleware([
    "auth",
    \App\Http\Middleware\CheckRole::class . ":admin,master,technician",
])->group(function () {
    Route::get("/knowledge", [KnowledgeBaseController::class, "index"])->name(
        "knowledge.index",
    );
    Route::get("/knowledge/{knowledge}", [
        KnowledgeBaseController::class,
        "show",
    ])->name("knowledge.show");
});

// Public homepage FAQ route
Route::get("/faq/{slug}", [HomepageFAQController::class, "show"])->name(
    "homepage-faq.show",
);
