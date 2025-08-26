<?php

if (!function_exists("user_has_role")) {
    /**
     * Проверить, имеет ли текущий пользователь определенную роль
     *
     * @param string|array $roles
     * @return bool
     */
    function user_has_role($roles)
    {
        $user = auth()->user();

        if (!$user || !$user->role) {
            return false;
        }

        return $user->hasRole($roles);
    }
}

if (!function_exists("user_can_manage_tickets")) {
    /**
     * Проверить, может ли пользователь управлять заявками
     *
     * @return bool
     */
    function user_can_manage_tickets()
    {
        return user_has_role(["admin", "master", "technician"]);
    }
}

if (!function_exists("user_can_manage_equipment")) {
    /**
     * Проверить, может ли пользователь управлять оборудованием
     *
     * @return bool
     */
    function user_can_manage_equipment()
    {
        return user_has_role(["admin", "master"]);
    }
}

if (!function_exists("user_can_manage_users")) {
    /**
     * Проверить, может ли пользователь управлять пользователями
     *
     * @return bool
     */
    function user_can_manage_users()
    {
        return user_has_role(["admin", "master"]);
    }
}

if (!function_exists("user_is_technician")) {
    /**
     * Проверить, является ли пользователь техником
     *
     * @return bool
     */
    function user_is_technician()
    {
        return user_has_role("technician");
    }
}

if (!function_exists("user_is_admin")) {
    /**
     * Проверить, является ли пользователь администратором
     *
     * @return bool
     */
    function user_is_admin()
    {
        return user_has_role("admin");
    }
}

if (!function_exists("format_ticket_status")) {
    /**
     * Форматировать статус заявки для отображения
     *
     * @param string $status
     * @return string
     */
    function format_ticket_status($status)
    {
        $statuses = [
            "open" => "Открыта",
            "in_progress" => "В работе",
            "resolved" => "Решена",
            "closed" => "Закрыта",
        ];

        return $statuses[$status] ?? $status;
    }
}

if (!function_exists("format_ticket_priority")) {
    /**
     * Форматировать приоритет заявки для отображения
     *
     * @param string $priority
     * @return string
     */
    function format_ticket_priority($priority)
    {
        $priorities = [
            "low" => "Низкий",
            "medium" => "Средний",
            "high" => "Высокий",
            "urgent" => "Срочный",
        ];

        return $priorities[$priority] ?? $priority;
    }
}

if (!function_exists("get_status_badge_class")) {
    /**
     * Получить CSS классы для badge статуса заявки
     *
     * @param string $status
     * @return string
     */
    function get_status_badge_class($status)
    {
        $classes = [
            "open" => "bg-blue-100 text-blue-800",
            "in_progress" => "bg-yellow-100 text-yellow-800",
            "resolved" => "bg-green-100 text-green-800",
            "closed" => "bg-slate-100 text-slate-800",
        ];

        return $classes[$status] ?? "bg-slate-100 text-slate-800";
    }
}

if (!function_exists("get_priority_badge_class")) {
    /**
     * Получить CSS классы для badge приоритета заявки
     *
     * @param string $priority
     * @return string
     */
    function get_priority_badge_class($priority)
    {
        $classes = [
            "low" => "bg-green-100 text-green-800",
            "medium" => "bg-yellow-100 text-yellow-800",
            "high" => "bg-red-100 text-red-800",
            "urgent" => "bg-red-200 text-red-900",
        ];

        // Для отладки: логируем входящий параметр и возвращаемое значение
        \Illuminate\Support\Facades\Log::debug("Priority badge requested", [
            "input_priority" => $priority,
            "returned_class" => isset($classes[$priority])
                ? $classes[$priority]
                : "bg-slate-100 text-slate-800",
        ]);

        return $classes[$priority] ?? "bg-slate-100 text-slate-800";
    }
}

if (!function_exists("truncate_text")) {
    /**
     * Обрезать текст до указанной длины
     *
     * @param string $text
     * @param int $length
     * @param string $suffix
     * @return string
     */
    function truncate_text($text, $length = 100, $suffix = "...")
    {
        if (strlen($text) <= $length) {
            return $text;
        }

        return substr($text, 0, $length) . $suffix;
    }
}

if (!function_exists("format_datetime")) {
    /**
     * Форматировать дату и время для отображения
     *
     * @param \Carbon\Carbon|string|null $datetime
     * @param string $format
     * @return string
     */
    function format_datetime($datetime, $format = "d.m.Y H:i")
    {
        if (!$datetime) {
            return "—";
        }

        if (is_string($datetime)) {
            $datetime = \Carbon\Carbon::parse($datetime);
        }

        return $datetime->format($format);
    }
}

if (!function_exists("format_date")) {
    /**
     * Форматировать дату для отображения
     *
     * @param \Carbon\Carbon|string|null $date
     * @return string
     */
    function format_date($date)
    {
        return format_datetime($date, "d.m.Y");
    }
}

if (!function_exists("get_room_display_name")) {
    /**
     * Получить отображаемое имя кабинета
     *
     * @param \App\Models\Room|null $room
     * @return string
     */
    function get_room_display_name($room)
    {
        if (!$room) {
            return "—";
        }

        $name = $room->number;

        if ($room->name) {
            $name .= " - " . $room->name;
        } elseif ($room->type_name) {
            $name .= " - " . $room->type_name;
        }

        return $name;
    }
}

if (!function_exists("get_ticket_icon")) {
    /**
     * Получить иконку для категории заявки
     *
     * @param string $category
     * @return string
     */
    function get_ticket_icon($category)
    {
        $icons = [
            "hardware" => "🖥️",
            "software" => "💾",
            "network" => "🌐",
            "account" => "👤",
            "other" => "❓",
        ];

        return $icons[$category] ?? "📝";
    }
}

if (!function_exists("can_manage_ticket")) {
    /**
     * Проверить, может ли пользователь управлять конкретной заявкой
     *
     * @param \App\Models\Ticket $ticket
     * @return bool
     */
    function can_manage_ticket($ticket)
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        // Админ/мастер могут управлять всеми заявками
        if ($user->hasRole(["admin", "master"])) {
            return true;
        }

        // Техник может управлять заявками, которые не закрыты
        if ($user->hasRole("technician") && $ticket->status !== "closed") {
            return true;
        }

        // Обычный пользователь — только свои
        return $ticket->user_id && $ticket->user_id === $user->id;
    }
}

if (!function_exists("is_current_route")) {
    /**
     * Проверить, является ли маршрут текущим
     *
     * @param string|array $routes
     * @return bool
     */
    function is_current_route($routes)
    {
        if (is_string($routes)) {
            $routes = [$routes];
        }

        foreach ($routes as $route) {
            if (request()->routeIs($route)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists("nav_link_class")) {
    /**
     * Получить CSS классы для навигационной ссылки
     *
     * @param string|array $routes
     * @param string $activeClass
     * @param string $inactiveClass
     * @return string
     */
    function nav_link_class(
        $routes,
        $activeClass = "text-blue-600 bg-blue-50",
        $inactiveClass = "text-gray-600 hover:text-blue-600 hover:bg-blue-50",
    ) {
        return is_current_route($routes) ? $activeClass : $inactiveClass;
    }
}
