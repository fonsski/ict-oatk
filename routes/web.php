<?php

use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\EquipmentCategoryController;
use App\Http\Controllers\EquipmentServiceController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\AllTicketsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\KnowledgeBaseController;
use App\Http\Controllers\KnowledgeCategoryController;
// Чота поменяли 
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\HomepageFAQController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ActivationController;
use App\Http\Controllers\PageController;
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
        Route::post("/api/tickets/{ticket}/status", [
            AllTicketsController::class,
            "updateStatus",
        ])->name("api.tickets.status");
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

        // API для назначения заявок и получения списка технических специалистов
        Route::post("/api/tickets/{ticket}/assign", [
            AllTicketsController::class,
            "quickAssign",
        ])->name("api.tickets.assign");

        Route::get("/api/users/technicians", [
            \App\Http\Controllers\Api\UserController::class,
            "technicians",
        ])->name("api.users.technicians");
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

        // Маршруты для обслуживания оборудования
        Route::get("/equipment/{equipment}/service", [
            EquipmentServiceController::class,
            "index",
        ])->name("equipment.service.index");

        Route::get("/equipment/{equipment}/service/create", [
            EquipmentServiceController::class,
            "create",
        ])->name("equipment.service.create");

        Route::post("/equipment/{equipment}/service", [
            EquipmentServiceController::class,
            "store",
        ])->name("equipment.service.store");

        Route::get("/equipment/{equipment}/service/{service}", [
            EquipmentServiceController::class,
            "show",
        ])->name("equipment.service.show");

        Route::get("/equipment/{equipment}/service/{service}/edit", [
            EquipmentServiceController::class,
            "edit",
        ])->name("equipment.service.edit");

        Route::put("/equipment/{equipment}/service/{service}", [
            EquipmentServiceController::class,
            "update",
        ])->name("equipment.service.update");

        Route::delete("/equipment/{equipment}/service/{service}", [
            EquipmentServiceController::class,
            "destroy",
        ])->name("equipment.service.destroy");

        Route::get(
            "/equipment/{equipment}/service/{service}/attachment/{index}",
            [EquipmentServiceController::class, "downloadAttachment"],
        )->name("equipment.service.attachment");

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

    // Маршруты для настроек
    Route::prefix("settings")->group(function () {
        Route::get("/", [
            \App\Http\Controllers\SettingsController::class,
            "index",
        ])->name("settings.index");
        Route::get("/change-password", [
            \App\Http\Controllers\SettingsController::class,
            "showChangePasswordForm",
        ])->name("settings.change-password.form");
        Route::post("/change-password", [
            \App\Http\Controllers\SettingsController::class,
            "changePassword",
        ])->name("settings.change-password");
    });

    // Маршрут для просмотра документации
    Route::get("/docs/{docName}.md", [
        \App\Http\Controllers\SettingsController::class,
        "viewDocumentation",
    ])->name("docs.view");
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
