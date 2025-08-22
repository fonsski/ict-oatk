<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->middleware("auth");
        $this->notificationService = $notificationService;
    }

    /**
     * Получить уведомления для текущего пользователя
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $limit = min((int) $request->get("limit", 10), 50);
        $unreadOnly = $request->boolean("unread_only", false);

        $notifications = $this->notificationService->getUserNotifications(
            $user,
            $limit,
            $unreadOnly,
        );
        $unreadCount = $this->notificationService->getUnreadCount($user);

        // Добавляем отладочную информацию
        \Log::debug("Notifications API response", [
            "user_id" => $user->id,
            "notifications_count" => $notifications->count(),
            "unread_count" => $unreadCount,
            "notifications" => $notifications->toArray(),
        ]);

        return response()->json([
            "notifications" => $notifications,
            "unread_count" => $unreadCount,
            "total_count" => $notifications->count(),
            "last_updated" => now()->toISOString(),
        ]);
    }

    /**
     * Получить количество непрочитанных уведомлений
     */
    public function unreadCount()
    {
        $user = Auth::user();
        $unreadCount = $this->notificationService->getUnreadCount($user);

        return response()->json([
            "unread_count" => $unreadCount,
            "has_unread" => $unreadCount > 0,
            "last_updated" => now()->toISOString(),
        ]);
    }

    /**
     * Отметить уведомление как прочитанное
     */
    public function markAsRead(Request $request, $notificationId)
    {
        $user = Auth::user();
        $this->notificationService->markAsRead($user, $notificationId);

        $unreadCount = $this->notificationService->getUnreadCount($user);

        return response()->json([
            "success" => true,
            "message" => "Уведомление отмечено как прочитанное",
            "unread_count" => $unreadCount,
        ]);
    }

    /**
     * Отметить все уведомления как прочитанные
     */
    public function markAllAsRead(Request $request)
    {
        $user = Auth::user();
        $this->notificationService->markAllAsRead($user);

        return response()->json([
            "success" => true,
            "message" => "Все уведомления отмечены как прочитанные",
            "unread_count" => 0,
        ]);
    }

    /**
     * Получить статистику уведомлений
     */
    public function stats()
    {
        $user = Auth::user();
        $stats = $this->notificationService->getNotificationStats($user);

        return response()->json([
            "stats" => $stats,
            "last_updated" => now()->toISOString(),
        ]);
    }

    /**
     * Проверить наличие новых уведомлений (для polling)
     */
    public function poll(Request $request)
    {
        $user = Auth::user();
        $lastCheck = $request->get("last_check");

        $unreadCount = $this->notificationService->getUnreadCount($user);
        $notifications = $this->notificationService->getUserNotifications(
            $user,
            5,
            true,
        );

        // Если есть timestamp последней проверки, фильтруем новые уведомления
        if ($lastCheck) {
            \Log::debug("Poll check with timestamp", [
                "last_check" => $lastCheck,
                "notifications" => $notifications->toArray(),
            ]);

            $notifications = $notifications->filter(function (
                $notification,
            ) use ($lastCheck) {
                $result = $notification["created_at"] > $lastCheck;
                \Log::debug("Notification filter check", [
                    "notification_date" => $notification["created_at"],
                    "last_check" => $lastCheck,
                    "passed_filter" => $result,
                ]);
                return $result;
            });
        }

        return response()->json([
            "has_new" => $notifications->isNotEmpty(),
            "new_notifications" => $notifications->values(),
            "unread_count" => $unreadCount,
            "last_updated" => now()->toISOString(),
        ]);
    }
}
