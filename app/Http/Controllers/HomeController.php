<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\HomepageFAQ;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        // Middleware auth уже применяется в маршрутах
        $this->notificationService = $notificationService;
    }

    public function index()
    {
        // Показать на главной активные FAQ
        $faqs = HomepageFAQ::active()->ordered()->take(6)->get();

        $tickets = collect();
        $ticketStats = null;

        // Если пользователь авторизован и имеет роль техника, мастера или админа - показать все заявки
        if (
            Auth::check() &&
            in_array(optional(Auth::user()->role)->slug, [
                "admin",
                "master",
                "technician",
            ])
        ) {
            $tickets = Ticket::with(["user", "location", "room", "assignedTo"])
                ->where("status", "!=", "closed")
                ->latest()
                ->take(10)
                ->get();

            // Статистика заявок для техника
            $allTickets = Ticket::all();
            $ticketStats = [
                "total" => $allTickets->count(),
                "open" => $allTickets->where("status", "open")->count(),
                "in_progress" => $allTickets
                    ->where("status", "in_progress")
                    ->count(),
                "resolved" => $allTickets->where("status", "resolved")->count(),
                "closed" => $allTickets->where("status", "closed")->count(),
            ];
        }

        // Код для тестовых уведомлений был удален

        return view("home", compact("faqs", "tickets", "ticketStats"));
    }

    /**
     * API для получения заявок на главной странице техника
     */
    public function technicianTicketsApi()
    {
        \Log::info("HomeController: technicianTicketsApi called");

        // Проверка, что пользователь авторизован и имеет нужную роль
        if (!Auth::check()) {
            \Log::warning("HomeController: User not authenticated");
            return response()->json(["error" => "Not authenticated"], 401);
        }

        $user = Auth::user();
        $userRole = optional($user->role)->slug;
        \Log::info("HomeController: User {$user->id} with role: {$userRole}");

        if (!in_array($userRole, ["admin", "master", "technician"])) {
            \Log::warning(
                "HomeController: User {$user->id} has insufficient permissions. Role: {$userRole}",
            );
            return response()->json(
                ["error" => "Insufficient permissions"],
                403,
            );
        }

        // Получение последних 10 заявок с полной информацией
        $tickets = Ticket::with(["user", "location", "room", "assignedTo"])
            ->where("status", "!=", "closed")
            ->latest()
            ->take(10)
            ->get();

        \Log::info("HomeController: Found {$tickets->count()} tickets");

        // Статистика всех заявок
        $allTickets = Ticket::all();
        $ticketStats = [
            "total" => $allTickets->count(),
            "open" => $allTickets->where("status", "open")->count(),
            "in_progress" => $allTickets
                ->where("status", "in_progress")
                ->count(),
            "resolved" => $allTickets->where("status", "resolved")->count(),
            "closed" => $allTickets->where("status", "closed")->count(),
        ];

        \Log::info("HomeController: Stats - " . json_encode($ticketStats));

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
                "reporter_email" => $ticket->reporter_email,
                "location_name" => $ticket->location
                    ? $ticket->location->name
                    : null,
                "room" => $ticket->room
                    ? [
                        "number" => $ticket->room->number,
                        "name" =>
                            $ticket->room->name ?? $ticket->room->type_name,
                        "full_address" => $ticket->room->full_address,
                    ]
                    : null,
                "assigned_to" => $ticket->assignedTo
                    ? $ticket->assignedTo->name
                    : null,
                "created_at" => $ticket->created_at->format("d.m H:i"),
                "updated_at" => $ticket->updated_at->format("d.m H:i"),
                "url" => route("tickets.show", $ticket),
            ];
        });

        $response = [
            "tickets" => $ticketsData,
            "stats" => $ticketStats,
            "last_updated" => now()->format("d.m.Y H:i:s"),
        ];

        \Log::info(
            "HomeController: Returning response with {$ticketsData->count()} tickets",
        );

        return response()->json($response);
    }
}
